from playwright.sync_api import sync_playwright
import time
import sys

def launch_dashboards():
    p = sync_playwright().start()

    # Launch browser in fullscreen
    browser = p.chromium.launch(
        headless=False,
        args=[
            '--start-maximized',
            '--start-fullscreen'
        ]
    )

    # Vendor Dashboard - Full Screen
    context = browser.new_context(
        viewport=None,
        no_viewport=True
    )
    page = context.new_page()

    print("Opening Vendor Login...")
    page.goto("http://127.0.0.1:8000/auth/vendor/login", wait_until="domcontentloaded")
    time.sleep(2)  # Wait for animations

    # Fill vendor login form
    print("Filling vendor credentials...")
    page.fill('input[name="email"]', 'maruthi4a5@gmail.com')
    page.fill('input[name="password"]', '12345678')
    page.click('button[type="submit"]')

    # Wait for dashboard to load
    page.wait_for_url("**/vendor/dashboard", timeout=15000)

    # Press F11 to ensure fullscreen
    page.keyboard.press('F11')

    print("[OK] Vendor Dashboard loaded in fullscreen!")

    print("\n" + "="*50)
    print("Vendor Dashboard is now open in FULLSCREEN!")
    print("="*50)
    print("\nVENDOR: http://127.0.0.1:8000/vendor/dashboard")
    print("\nPress F11 to exit fullscreen")
    print("Press Ctrl+C in terminal to close browser")
    sys.stdout.flush()

    # Keep browser open indefinitely
    try:
        while True:
            time.sleep(60)  # Sleep for a minute at a time
    except KeyboardInterrupt:
        print("\nClosing browser...")
        browser.close()
        p.stop()

if __name__ == "__main__":
    launch_dashboards()
