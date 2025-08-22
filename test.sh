#!/bin/bash

# PartyMinder Quick Test Script
# Run this after making changes to verify nothing is broken

echo "🚀 Running PartyMinder Smoke Tests..."
echo

# Check if we're in the right directory
if [ ! -f "partyminder.php" ]; then
    echo "❌ Error: Run this script from the PartyMinder plugin directory"
    echo "Expected to find partyminder.php in current directory"
    exit 1
fi

# Run the smoke test
php tests/smoke-test.php

# Check exit code
if [ $? -eq 0 ]; then
    echo
    echo "✅ All tests passed! Ready to deploy."
else
    echo
    echo "❌ Some tests failed. Fix issues before deploying."
    exit 1
fi