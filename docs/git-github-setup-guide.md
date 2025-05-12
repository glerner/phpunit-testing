# Git and GitHub Setup Guide

This guide walks through the process of creating a local Git repository and connecting it to GitHub.

## Creating a Local Git Repository

1. Navigate to your project directory:
   ```bash
   cd ~/sites/your-project-name
   ```

2. Initialize a new Git repository:
   - Note: This works in both empty folders and folders with existing code
   ```bash
   git init
   ```

3. Rename the default branch to "main" (modern standard):
   ```bash
   git branch -m main
   ```

4. Add your files to the repository:
   ```bash
   git status

   git add .
   # or with list of files added
   git add . --verbose
   ```
   - The '.' is to add all files. Instead of all files, you can add specific files:
   ```bash
   git add filename1 filename2
   ```
   - Tip: Always run `git status` before `git add .` to verify what will be added

5. Check the status of your repository:
   ```bash
   git status
   ```

6. Create a basic README.md file (if you don't have one already):
   ```bash
   echo "# Project Name\n\nBrief description of your project" > README.md
   git add README.md
   ```

7. Create your initial commit:
   ```bash
   git commit -m "Initial commit: Brief description of your project"
   ```

## Creating a GitHub Repository

1. Go to [GitHub.com](https://github.com/new) and sign in
2. Click "New" to create a new repository
3. Name your repository (use the same name as your local project for clarity)
4. Add an optional description
5. Choose public or private visibility
6. **Do not** initialize with README, license, or .gitignore (since we already have a local repository, create locally)
7. Click "Create repository"

## Connecting Local Repository to GitHub

1. Set up GitHub authentication using a Personal Access Token (PAT):
   - Go to GitHub.com → Settings → Developer settings → Personal access tokens → Tokens (classic)
   - Click "Generate new token (classic)"
   - Give it a descriptive name (e.g., "Local Development Machine")
   - Set an expiration date (e.g., 90 days)
   - Select scopes:
     - At minimum, check `repo` for repository access
     - Add other scopes as needed (e.g., `workflow` for GitHub Actions)

     **Recommended Scopes for WordPress Developers:**
     - `repo` - Full control of private repositories (includes all repo sub-scopes)
     - `workflow` - If using GitHub Actions for CI/CD or deployments
     - `read:packages` - If consuming GitHub packages

     **Recommended Scopes for Small Teams:**
     - Consider using fine-grained tokens instead of classic tokens
     - If using classic tokens, limit to specific repositories when possible
     - For organization-wide access: `repo`, `workflow`, `read:org`

   - Click "Generate token"
   - **IMPORTANT**: Copy the token immediately - you won't be able to see it again!
   - Store the token securely (e.g., in a password manager like Bitwarden, LastPass, or iPassword)

   ### Personal Access Token Strategy

   You have two main options for managing Personal Access Tokens:

   #### Option 1: Single PAT for All Repositories (Simpler)
   - **Pros**: Easier to manage, only need to remember/update one token
   - **Cons**: If compromised, attacker gains access to all your repositories
   - **Best for**: Individual developers or small teams with trusted environments
   - **Implementation**: Use a single token with appropriate scopes for all your repositories
   - **Storage**: Save in your global Git credential helper

   #### Option 2: Separate PATs per Project or Project Group (More Secure)
   - **Pros**: Better security through isolation, limits potential damage if compromised
   - **Cons**: More tokens to manage and update when they expire
   - **Best for**: Sensitive projects, team environments, or professional settings
   - **Implementation**: Create tokens with names like "Project X Access" with minimal required scopes
   - **Storage**: Store in project-specific credential files or use different credential helpers, or store each in your Password Keeper software to paste each time

   #### Security Best Practices
   - Always set an expiration date (30-90 days recommended)
   - Use the minimum required scopes for each token
   - Never commit tokens to your repository
   - Regularly audit and revoke unused tokens
   - Consider using GitHub's fine-grained tokens for more granular control

2. Add the GitHub repository as a remote:
   ```bash
   # If you need to remove an existing remote
   git remote remove origin

   # Add the GitHub repository as "origin" using HTTPS
   git remote add origin https://github.com/yourusername/your-repository-name.git
   ```

3. Push your local repository to GitHub:
   ```bash
   git push -u origin main
   ```
   - When prompted, use your GitHub username and your Personal Access Token as the password
   - The `-u` (or `--set-upstream`) flag sets up tracking between your local and remote branches
   - This only needs to be done once; after this, you can simply use `git push` for future pushes
   - The tracking information is stored in your `.git/config` file

4. Verify your repository settings:
   ```bash
   git config --list

   # To see where each setting comes from (global vs. local)
   git config --list --show-origin --show-scope
   ```

   ### Recommended Git Configuration

   #### Global Settings (in `~/.gitconfig`)

   These settings should be applied globally as they apply to all repositories.
   Run these from your Shell or Bash:

   ```bash
   # Identity settings
   git config --global user.name "Your Name"
   git config --global user.email "your.email@example.com"

   # Credential helper to avoid typing your password repeatedly
   git config --global credential.helper store

   # Default pull behavior (prevents unintended merges)
   git config --global pull.rebase true

   # Enable helpful coloring in Git output
   git config --global color.ui auto

   # Line ending normalization
   git config --global core.autocrlf input  # For Linux/Mac
   # git config --global core.autocrlf true # For Windows
   ```

   #### Local Settings (in `.git/config`)

   These settings are automatically created for each repository or should be set per-project:

   ```ini
   [core]
   	repositoryformatversion = 0
   	filemode = true
   	bare = false
   	logallrefupdates = true
   [remote "origin"]
   	url = https://github.com/username/repository.git
   	fetch = +refs/heads/*:refs/remotes/origin/*
   [branch "main"]
   	remote = origin
   	merge = refs/heads/main
   ```

   ### Checking Your Configuration

   A properly configured repository will show both global and local settings:

   ```
   global  file:/home/username/.gitconfig    user.name=Your Name
   global  file:/home/username/.gitconfig    user.email=your.email@example.com
   global  file:/home/username/.gitconfig    credential.helper=store
   global  file:/home/username/.gitconfig    pull.rebase=true
   global  file:/home/username/.gitconfig    color.ui=auto
   local   file:.git/config                  core.repositoryformatversion=0
   local   file:.git/config                  core.filemode=true
   local   file:.git/config                  core.bare=false
   local   file:.git/config                  core.logallrefupdates=true
   local   file:.git/config                  remote.origin.url=https://github.com/username/repository.git
   local   file:.git/config                  remote.origin.fetch=+refs/heads/*:refs/remotes/origin/*
   local   file:.git/config                  branch.main.remote=origin
   local   file:.git/config                  branch.main.merge=refs/heads/main
   ```

## Daily Workflow

### Making Changes

1. Make changes to your files
2. Review your changes:
   ```bash
   # First check what files have changed
   git status

   # Then examine the actual changes in detail
   git diff

   # Optionally save the diff to a file for AI assistance with commit messages
   git diff >junk.txt
   ```

   - Tip: After saving the diff to junk.txt, ask AI "Make a git commit message for these changes:" followed by the full text of the diff. Review the AI's result.
   - Note: It's best to do this review BEFORE running git add, as it's easier to see what's changed

3. Stage your changes:
   ```bash
   git add .
   ```

4. Commit your changes:
   ```bash
   git commit -m "Descriptive message about your changes"
   ```
   - For multi-line commit messages, just use `git commit` and a text editor will open

5. Push your changes to GitHub:
   ```bash
   git push
   ```

### Getting Changes from GitHub

If you're working across multiple machines or with collaborators:

```bash
git pull
```

## Helpful Git Configurations

Set your identity:
```bash
git config --global user.name "Your Name"
git config --global user.email "your.email@example.com"
```

Store credentials to avoid typing your token repeatedly:
```bash
git config --global credential.helper store
```

## Troubleshooting

### "Remote origin already exists" error
```bash
git remote remove origin
git remote add origin https://github.com/yourusername/your-repository-name.git
```

### Checking your remote URL
```bash
git remote -v
```

### Changing from SSH to HTTPS URL
```bash
git remote set-url origin https://github.com/yourusername/your-repository-name.git
```
- Note: SSH connections are considered obsolete for GitHub. Personal Access Tokens with HTTPS URLs are now the recommended approach for authentication.
