#!/bin/bash
# Deploy all Brand Monitor custom actors to Apify

set -e

echo "========================================="
echo "Brand Monitor - Deploy Custom Actors"
echo "========================================="
echo ""

# Check if apify-cli is installed
if ! command -v apify &> /dev/null; then
    echo "Error: apify-cli is not installed."
    echo "Install with: npm install -g apify-cli"
    exit 1
fi

# Check if logged in
if ! apify info &> /dev/null; then
    echo "Error: Not logged in to Apify."
    echo "Login with: apify login"
    exit 1
fi

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ACTORS=("twitter-scraper" "instagram-scraper" "reddit-scraper" "news-scraper" "reviews-scraper")

echo "Deploying ${#ACTORS[@]} actors..."
echo ""

DEPLOYED=0
FAILED=0

for actor in "${ACTORS[@]}"; do
    ACTOR_DIR="$SCRIPT_DIR/$actor"

    if [ -d "$ACTOR_DIR" ]; then
        echo "----------------------------------------"
        echo "Deploying: $actor"
        echo "----------------------------------------"

        cd "$ACTOR_DIR"

        # Install dependencies
        echo "Installing dependencies..."
        npm install --silent

        # Push to Apify
        echo "Pushing to Apify..."
        if apify push; then
            echo "✓ $actor deployed successfully"
            ((DEPLOYED++))
        else
            echo "✗ $actor deployment failed"
            ((FAILED++))
        fi

        echo ""
    else
        echo "Warning: Directory not found: $ACTOR_DIR"
        ((FAILED++))
    fi
done

echo "========================================="
echo "Deployment Summary"
echo "========================================="
echo "Deployed: $DEPLOYED"
echo "Failed: $FAILED"
echo ""

if [ $FAILED -eq 0 ]; then
    echo "All actors deployed successfully!"
    echo ""
    echo "Next steps:"
    echo "1. Get your Apify username: apify info"
    echo "2. Update backend/app/scrapers/apify_orchestrator.py"
    echo "   Change actor_id to: YOUR_USERNAME/brand-monitor-ACTOR-NAME"
    echo ""
else
    echo "Some deployments failed. Check errors above."
    exit 1
fi
