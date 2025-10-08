# How to Use Claude Code Design Workflows

## Overview
We've installed the **Claude Code Workflows** from GitHub, specifically the **Design Review Workflow** that provides automated UI/UX reviews using Playwright browser automation and specialized agents.

## Key Components Installed

### 1. Design Review Agent (`@agent-design-review`)
A specialized agent that conducts comprehensive design reviews following standards from top companies (Stripe, Airbnb, Linear).

### 2. Review Process (7 Phases)
1. **Preparation** - Analyze changes and set up preview
2. **Interaction Testing** - Test all interactive states
3. **Responsiveness** - Test desktop/tablet/mobile viewports
4. **Visual Polish** - Check layout, typography, colors
5. **Accessibility** - WCAG 2.1 AA compliance
6. **Robustness** - Edge cases and error states
7. **Code Health** - Component reuse and patterns

### 3. Required Tools
The workflow requires **Playwright MCP** server for browser automation:
- `mcp__playwright__browser_navigate` - Navigate to pages
- `mcp__playwright__browser_take_screenshot` - Capture evidence
- `mcp__playwright__browser_resize` - Test viewports
- `mcp__playwright__browser_click/type` - Interact with UI
- `mcp__playwright__browser_console_messages` - Check for errors

## How to Use in Our Project

### Method 1: Tag the Design Review Agent
```
@agent-design-review
```
This invokes the specialized design review agent to analyze your UI changes.

### Method 2: Quick Visual Check (After Any UI Change)
Follow this checklist immediately after implementing front-end changes:

1. **Identify what changed** - List modified components
2. **Navigate to pages** - Visit each changed view
3. **Verify design compliance** - Check against our design principles
4. **Validate implementation** - Ensure it meets requirements
5. **Capture screenshots** - Desktop viewport (1440px)
6. **Check console** - Look for errors

### Method 3: Comprehensive Review Command
For thorough design validation, use when:
- Completing significant UI/UX features
- Before finalizing PRs
- Need accessibility testing
- Testing responsive design

## Our Project-Specific Setup

### Design Principles Location
- Main principles: `.claude/vendor-dashboard-design-principles.md`
- Color system: `public/assets/css/vendor/dashboard/colors.css`
- Layout rules: `public/assets/css/vendor/dashboard/layout.css`

### Key URLs for Testing
- Vendor Dashboard: `http://localhost:8000/vendor/dashboard`
- Buyer Dashboard: `http://localhost:8000/buyer/dashboard`
- Login: `http://localhost:8000/vendor/login`

### Viewports to Test
- **Desktop**: 1440 x 900px
- **Tablet**: 768 x 1024px
- **Mobile**: 375 x 812px

## Example Workflow for Vendor Dashboard

### Step 1: Make UI Changes
```
"Update the vendor dashboard header with new navigation"
```

### Step 2: Run Quick Check
```
1. Navigate to http://localhost:8000/vendor/dashboard
2. Check if header matches design system
3. Test hover states on navigation
4. Verify neumorphic shadows
5. Screenshot at 1440px width
```

### Step 3: Request Comprehensive Review
```
@agent-design-review check the vendor dashboard for:
- Accessibility compliance
- Responsive design
- Visual consistency with buyer dashboard
- Performance issues
```

### Step 4: Review Report Format
The agent will provide:
```markdown
### Design Review Summary
[Overall assessment]

### Findings

#### Blockers
- [Critical issues requiring immediate fix]

#### High-Priority
- [Significant issues to fix before merge]

#### Medium-Priority
- [Improvements for follow-up]

#### Nitpicks
- Nit: [Minor aesthetic details]
```

## Parallel Design Development

Our configuration spawns multiple agents in parallel:

1. **UI Architect** (2 instances) - Design components
2. **UX Flow Engineer** (1 instance) - User journeys
3. **Implementation Executor** (2 instances) - Build code
4. **Design Review** (1 instance) - Validate quality

Simply mention design tasks and agents auto-spawn:
- "Design vendor stats widgets"
- "Create product grid layout"
- "Build order management interface"

## Best Practices

1. **Always test after changes** - Run quick visual check
2. **Capture screenshots** - Document visual state
3. **Check multiple viewports** - Ensure responsive
4. **Validate interactions** - Test all hover/click states
5. **Review accessibility** - Keyboard navigation, contrast
6. **Check console** - No errors or warnings

## Troubleshooting

### If Playwright MCP is not available:
- The workflow requires Playwright MCP server
- Install from: https://github.com/microsoft/playwright-mcp
- Or perform manual visual checks

### If agents don't spawn:
- Check `.claude/settings.json` for configuration
- Verify `auto_spawn: true` is set
- Use explicit agent tags: `@agent-design-review`

### For parallel execution issues:
- Ensure `force_parallel: true` in settings
- Check `min_agents: 4` configuration
- Review `.claude/auto-spawn-design.json`

## Current Project Status

✅ **Installed:**
- Claude Code Workflows repository
- Design Review workflow files
- Custom parallel design agents
- Auto-spawn configuration

✅ **Configured:**
- Vendor dashboard with buyer's design system
- Neumorphic color palette (#E0E5EC)
- Floating icon header (matches buyer)
- Design principles documentation

⏳ **Next Steps:**
1. Install Playwright MCP if needed
2. Run design review on vendor dashboard
3. Build remaining dashboard components
4. Test across all viewports

## Commands Summary

- `@agent-design-review` - Invoke design review agent
- Mention "design" keywords - Auto-spawns design team
- Follow quick check steps - After any UI change
- Request comprehensive review - For major features