# PowerShell helper to create a GitHub repo and push local project
# Usage: Run in PowerShell from the project root: .\create_remote_and_push.ps1

param(
    [string]$githubUser = "nedarta",
    [string]$repoName = "yii2-calendar-widget",
    [ValidateSet("public","private")]
    [string]$visibility = "public",
    [ValidateSet("ssh","https")]
    [string]$protocol = "ssh"
)

Write-Output "This script will attempt to create the GitHub repository '$githubUser/$repoName' and push the current folder.\n"

# Ensure git is initialized
if (-not (Test-Path .git)) {
    git init
    git branch -M main
    git add -A
    git commit -m "Initial commit"
} else {
    Write-Output ".git already exists. Skipping git init and initial commit."
}

# Prefer GitHub CLI if available
if (Get-Command gh -ErrorAction SilentlyContinue) {
    Write-Output "Found gh CLI. Creating repo via gh..."
    gh auth status 2>$null || gh auth login
    gh repo create $githubUser/$repoName --$visibility --source=. --remote=origin --push
    Write-Output "Created and pushed via gh."
    gh repo view $githubUser/$repoName --web
    return
}

# Fallback: provide manual remote commands
if ($protocol -eq 'ssh') {
    $remoteUrl = "git@github.com:$githubUser/$repoName.git"
} else {
    $remoteUrl = "https://github.com/$githubUser/$repoName.git"
}

Write-Output "Run the following commands manually in PowerShell to add remote and push (or run this script with admin privileges):\n"
Write-Output "git remote add origin $remoteUrl"
Write-Output "git push -u origin main"

Write-Output "If the remote already exists, run: git remote set-url origin $remoteUrl"


