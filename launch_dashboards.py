from playwright.sync_api import sync_playwright
import time
import sys

def launch_dashboards():
    p = sync_playwright().start()

    # Launch three separate browser instances in fullscreen
    browser1 = p.chromium.launch(
        headless=False,
        args=['--start-maximized', '--start-fullscreen']
    )
    browser2 = p.chromium.launch(
        headless=False,
        args=['--start-maximized', '--start-fullscreen']
    )
    browser3 = p.chromium.launch(
        headless=False,
        args=['--start-maximized', '--start-fullscreen']
    )

    # Browser 1: Welcome Page (fullscreen viewport)
    context1 = browser1.new_context(viewport=None, no_viewport=True)
    page1 = context1.new_page()

    print("Opening Welcome Page...")
    page1.goto("http://127.0.0.1:8000")
    page1.wait_for_load_state("networkidle")
    print("[OK] Welcome Page loaded")

    # Browser 2: Buyer Dashboard (fullscreen viewport)
    context2 = browser2.new_context(viewport=None, no_viewport=True)
    page2 = context2.new_page()

    print("Opening Buyer Login...")
    page2.goto("http://127.0.0.1:8000/auth/buyer/login")
    page2.wait_for_load_state("domcontentloaded")

    # Wait for form to be interactive (animations complete)
    page2.wait_for_selector('input[placeholder="Your Email Address"]', state="visible", timeout=15000)
    time.sleep(1.5)  # Extra wait for animations

    # Fill buyer login form
    page2.fill('input[placeholder="Your Email Address"]', 'maruthi4a5@gmail.com')
    page2.fill('input[placeholder="Password"]', 'Maruthi4@5')

    # Click submit button
    page2.click('button:has-text("Sign In to Buy")')

    # Wait for dashboard to load
    page2.wait_for_url("**/buyer/dashboard", timeout=15000)
    print("[OK] Buyer Dashboard loaded")

    # Browser 3: Vendor Dashboard (fullscreen viewport)
    context3 = browser3.new_context(viewport=None, no_viewport=True)
    page3 = context3.new_page()

    print("Opening Vendor Login...")
    page3.goto("http://127.0.0.1:8000/auth/vendor/login")
    page3.wait_for_load_state("domcontentloaded")

    # Wait for form to be interactive (animations complete)
    page3.wait_for_selector('input[placeholder="Business Email"]', state="visible", timeout=15000)
    time.sleep(1.5)  # Extra wait for animations

    # Fill vendor login form
    page3.fill('input[placeholder="Business Email"]', 'maruthi4a5@gmail.com')
    page3.fill('input[placeholder="Password"]', '12345678')

    # Click submit button
    page3.click('button:has-text("Sign In")')

    # Wait for dashboard to load
    page3.wait_for_url("**/vendor/dashboard", timeout=15000)
    print("[OK] Vendor Dashboard loaded")

    print("\n" + "="*60)
    print("All dashboards are now open!")
    print("="*60)
    print("\nWELCOME: http://127.0.0.1:8000")
    print("BUYER:   http://127.0.0.1:8000/buyer/dashboard")
    print("VENDOR:  http://127.0.0.1:8000/vendor/dashboard")
    print("\nPress Ctrl+C in terminal to close all browsers")
    sys.stdout.flush()

    # Keep browsers open indefinitely
    try:
        while True:
            time.sleep(60)
    except KeyboardInterrupt:
        print("\nClosing browsers...")
        browser1.close()
        browser2.close()
        browser3.close()
        p.stop()

if __name__ == "__main__":
    launch_dashboards()
