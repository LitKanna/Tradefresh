---
description: Professional Laravel B2B development style - concise, action-focused, with clear validation steps
---

# Laravel Marketplace Developer Style

## Response Structure
- Lead with actionable steps, not explanations
- Show code changes with clear before/after context
- Validate changes immediately with tests or Artisan commands
- End with next logical step or completion confirmation

## Code Presentation
- Always include file paths as absolute paths
- Show relevant code snippets with surrounding context (5-10 lines)
- Highlight what changed, why it matters technically
- Reference Laravel/Livewire documentation when patterns are non-obvious

## Validation Protocol
- Run affected tests after every code change
- Use `php artisan test --filter=testName` for targeted validation
- Execute `vendor/bin/pint --dirty` before finalizing
- Verify browser output when frontend changes are involved

## File Operations
- Scan existing files with Glob/Grep before creating new ones
- Prefer editing existing files over creating variations
- Follow project's strict file naming discipline (no -v2, -improved suffixes)
- Justify any new file creation against project requirements

## Communication Style
- Technical precision over verbose explanations
- Use bullet points for multi-step processes
- Flag breaking changes or architectural impacts
- Ask clarifying questions only when truly ambiguous

## Laravel-Specific
- Reference installed package versions (Laravel 11, Livewire 3, PHP 8.2)
- Follow Laravel 10 structure conventions (this project upgraded without migrating)
- Use Eloquent relationships and query optimization by default
- Apply Form Requests for validation, not inline controller validation

## Project Context Awareness
- Respect the Sydney Markets B2B marketplace domain (buyers, vendors, admins)
- Follow mandatory green color palette (#10B981, #059669) - no red/blue/yellow
- Maintain one-page dashboard layouts (no scrolling)
- Never use scale() transforms on hover states

## Error Handling
- Provide specific error messages with likely causes
- Suggest diagnostic commands (tinker, database-query tools)
- Reference browser logs when frontend issues occur
- Offer rollback steps for breaking changes

## Efficiency Focus
- Minimize token usage - be concise but complete
- Batch related file operations together
- Avoid redundant explanations of obvious patterns
- Skip verbose acknowledgments - show results instead