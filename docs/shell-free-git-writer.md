# EduCore shell-free Git writer

`SelfGitCommitController` creates and pushes repository commits without invoking a local Git binary, cPanel Git deployment, or GitHub Actions. It talks directly to GitHub's Git Database API and advances an approved branch with a non-forced fast-forward update.

## Server configuration

Configure these values only in the server environment. Never put the GitHub token in a URL, request payload, repository file, or client-side application.

```dotenv
GITHUB_WRITE_TOKEN=<fine-grained token stored only on the server>
GITHUB_COMMIT_BRANCHES=mobile-overhaul
GITHUB_COMMIT_ALLOW_MASTER=false
```

`GITHUB_WRITE_TOKEN` should be restricted to this repository and should have **Contents: Read and write** permission. GitHub Actions permission is not required for the normal EduCore source paths written by this controller.

`GITHUB_COMMIT_ALLOW_MASTER` is deliberately false. Keep production/master writes disabled while the mobile overhaul is under review.

The caller authenticates with the existing EduCore deploy token in a header:

```http
X-EduCore-Deploy-Token: <DEPLOY_TOKEN>
```

`Authorization: Bearer <DEPLOY_TOKEN>` is also accepted. Do not transmit `GITHUB_WRITE_TOKEN` to the endpoint.

## 1. Read the branch head

```http
GET /deploy/repository/status?branch=mobile-overhaul
X-EduCore-Deploy-Token: <DEPLOY_TOKEN>
```

The response contains `head_sha`. Preserve that value and submit it as `expected_head_sha` with the commit. This is optimistic locking: if another writer advances the branch first, EduCore rejects the stale request instead of overwriting concurrent work.

## 2. Create and push one atomic commit

```http
POST /deploy/repository/commit
Content-Type: application/json
X-EduCore-Deploy-Token: <DEPLOY_TOKEN>

{
  "branch": "mobile-overhaul",
  "expected_head_sha": "0123456789abcdef0123456789abcdef01234567",
  "message": "Describe the repository change",
  "changes": [
    {
      "path": "educore/app/Example.php",
      "content": "<?php\n"
    },
    {
      "path": "docs/retired-note.md",
      "delete": true
    }
  ]
}
```

The controller creates every blob first, builds one tree, creates one commit with the inspected branch head as its parent, then updates the branch ref with `force=false`. This is the equivalent of an atomic multi-file commit followed by a normal push.

A `409 branch_head_changed` means the initial `expected_head_sha` is stale. Fetch status again, reconcile the new head, and retry. A `409 concurrent_branch_update` means another writer advanced the branch after the commit object had been created; EduCore reports the orphan commit SHA but does not force it onto the branch.

## Safety boundaries

The writer accepts only approved repository branches and approved repository paths, refuses `.env` and runtime `storage` content, rejects traversal and duplicate paths, caps each request at 50 changes and 2 MB of text, and never force-pushes. GitHub credentials remain server-side.

This controller handles **commit and push only**. Android compilation, Laravel tests, APK signing, release verification, merge to `master`, and production deployment remain separate operations. A successful repository write must not be interpreted as a successful Android build or release.
