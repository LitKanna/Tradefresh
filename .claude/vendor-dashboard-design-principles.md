# Vendor Dashboard Design Principles

## Visual Design System

### Color Palette
- **Primary Background**: #E0E5EC (Neumorphic gray)
- **Text Primary**: #2A2620 (Rich warm black)
- **Accent Green**: #5CB85C (Fresh produce green)
- **Golden Honey**: #F0A830 (Pricing/highlights)

### Neumorphic Design
- **Card Shadow Normal**: `inset 5px 5px 10px #B8BEC7, inset -5px -5px 10px #FFFFFF`
- **Card Shadow Hover**: `inset 8px 8px 15px #B8BEC7, inset -8px -8px 15px #FFFFFF`
- **Raised Elements**: `9px 9px 16px #B8BEC7, -9px -9px 16px #FFFFFF`

### Layout Principles
1. **Grid System**: 12-column responsive grid
2. **No Scroll**: Everything fits on screen (1080p-4K adaptive)
3. **Sections**:
   - Header: 80px height, sticky
   - Stats: 120px height, 4 columns
   - Main Content: 8 columns
   - Sidebar: 4 columns

### Component Guidelines
1. **Cards**: Neumorphic with inset shadows
2. **Buttons**: Green gradient for primary actions
3. **Icons**: Outline style, 2px stroke
4. **Forms**: Clean, minimal, well-labeled

## Interaction Patterns

### Hover States
- NO scale transforms (causes jitter)
- Use translateY for vertical movement
- Deepen shadows on hover
- Subtle color shifts

### Transitions
- Fast: 150ms (tooltips, dropdowns)
- Base: 250ms (most interactions)
- Slow: 350ms (page transitions)

### Loading States
- Skeleton screens for content
- Pulse animations for activity
- Progress bars for operations

## Accessibility Standards

### WCAG 2.1 AA Compliance
- Color contrast: 4.5:1 minimum
- Focus states: Visible on all interactive elements
- Keyboard navigation: Full support
- Screen reader: Semantic HTML

### Touch Targets
- Minimum: 44x44px
- Spacing: 8px between targets
- Clear active states

## Content Guidelines

### Typography Hierarchy
- H1: 30px (1.875rem) - Page titles
- H2: 24px (1.5rem) - Section headers
- H3: 20px (1.25rem) - Subsections
- Body: 16px (1rem) - Regular text
- Small: 14px (0.875rem) - Captions

### Writing Style
- Clear, concise, professional
- Action-oriented button text
- Helpful error messages
- Consistent terminology

## Performance Standards

### Loading Times
- Initial load: < 2 seconds
- Interaction response: < 100ms
- Page transitions: < 300ms

### Optimization
- Lazy load images
- Minimize re-renders
- Cache static content
- Optimize bundle size

## Vendor Dashboard Specific

### Key Sections
1. **Stats Dashboard**: Sales, Orders, Products, Revenue
2. **Product Management**: Grid view, quick edit
3. **Order Processing**: List view, status updates
4. **RFQ Panel**: Active quotes, responses
5. **Analytics**: Charts, trends, insights

### User Flows
1. **Quick Actions**: One-click access to common tasks
2. **Bulk Operations**: Select multiple, apply action
3. **Real-time Updates**: Live data refresh
4. **Mobile Responsive**: Full functionality on all devices

### Business Rules
- Show vendor-specific data only
- Highlight urgent items (RFQs, low stock)
- Display performance metrics prominently
- Enable quick product updates

## Design Review Checklist

- [ ] Colors match design system
- [ ] Neumorphic shadows applied correctly
- [ ] Layout responsive across viewports
- [ ] Interactions smooth and consistent
- [ ] Accessibility standards met
- [ ] Performance targets achieved
- [ ] Content clear and professional
- [ ] Business requirements fulfilled