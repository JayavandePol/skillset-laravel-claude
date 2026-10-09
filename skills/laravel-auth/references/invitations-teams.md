# Invitations & team/workspace membership

## Tables

```
workspaces          id, ulid, name, slug (unique), owner_id → users (restrict), timestamps
workspace_user      workspace_id, user_id (PK both), joined_at, timestamps
invitations         id, workspace_id (cascade), email, role, token_hash (unique), invited_by (null on delete),
                    expires_at, accepted_at, timestamps; unique(workspace_id, email) where accepted_at is null
users.current_workspace_id (nullable, null on delete) — for the workspace switcher
```

Roles per workspace via `spatie/laravel-permission` teams (see `laravel-spatie`) or a `role` column on the pivot for simple apps.

## Invite flow (AUTH-08)

1. `InviteMember` Action (authorized by `members.manage`, rate limited): create invitation with `token = Str::random(64)`, store `hash('sha256', $token)`, expiry 7 days; queue `InvitationMail` with a URL containing the plain token.
2. Accept route `GET /invitations/{token}`:
   - Look up by hash; reject expired/accepted (show friendly page).
   - Guest: show login/register with email prefilled; after auth, continue.
   - Logged in: email must match the invitation (case-insensitive), otherwise show "this invite is for x@…".
3. `AcceptInvitation` Action in a transaction: attach to workspace, assign role (with `setPermissionsTeamId`), mark accepted, set `current_workspace_id`, dispatch `MemberJoined`.
4. Owners can revoke and resend (new token, old one invalidated).

## Switching workspaces

- `PUT /current-workspace` with workspace ulid → verify membership → update `current_workspace_id` → redirect to that workspace's dashboard.
- Middleware for path-based tenancy still checks membership on each request.

## Ownership & leaving

- Exactly one owner; transferring ownership is an explicit Action requiring `password.confirm`.
- Owners can't leave without transferring; deleting a workspace requires typed confirmation and is queued (media, exports).
- Removing a member revokes their roles in that team and their workspace-bound API tokens.
