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
6. **Do not** initialize with README, license, or .gitignore (since we already have a local repository)
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
   - Click "Generate token"
   - **IMPORTANT**: Copy the token immediately - you won't be able to see it again!
   - Store the token securely (e.g., in a password manager like Bitwarden, LastPass, or iPassword)

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

4. Verify your repository settings:
   ```bash
   git config --list
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
