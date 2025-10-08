---
description: Launch buyer and vendor dashboards in separate browsers
allowed-tools: Write, Bash
---

Create a Python script that launches two Playwright browsers - one for buyer dashboard and one for vendor dashboard - and run it.

Create the file `launch_dashboards.py` with:

```python
from playwright.sync_api import sync_playwright
import time

def launch_dashboards():
    with sync_playwright() as p:
        # Launch two browser instances
        browser1 = p.chromium.launch(headless=False)
        browser2 = p.chromium.launch(headless=False)

        # Browser 1: Buyer Dashboard
        context1 = browser1.new_context()
        page1 = context1.new_page()

        print("Opening Buyer Dashboard...")
        page1.goto("http://127.0.0.1:8000/buyer/login")
        page1.wait_for_load_state("networkidle")

        # Fill buyer login form
        page1.fill('input[name="email"]', 'maruthi4a5@gmail.com')
        page1.fill('input[name="password"]', 'Maruthi4@5')
        page1.press('input[name="password"]', 'Enter')

        # Wait for dashboard to load
        page1.wait_for_url("**/buyer/dashboard", timeout=10000)
        print("✓ Buyer Dashboard loaded")

        # Browser 2: Vendor Dashboard
        context2 = browser2.new_context()
        page2 = context2.new_page()

        print("Opening Vendor Dashboard...")
        page2.goto("http://127.0.0.1:8000/vendor/login")
        page2.wait_for_load_state("networkidle")

        # Fill vendor login form
        page2.fill('input[name="email"]', 'maruthi4a5@gmail.com')
        page2.fill('input[name="password"]', '12345678')
        page2.press('input[name="password"]', 'Enter')

        # Wait for dashboard to load
        page2.wait_for_url("**/vendor/dashboard", timeout=10000)
        print("✓ Vendor Dashboard loaded")

        print("\nBoth dashboards are now open. Press Enter to close browsers...")
        input()

        browser1.close()
        browser2.close()

if __name__ == "__main__":
    launch_dashboards()
```

Then run it with: `python launch_dashboards.py`