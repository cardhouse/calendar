# Family Morning Dashboard

## Project Overview

The Family Morning Dashboard is a Laravel-based web application designed to be displayed on a screen every morning to help families organize their day. It serves as a central hub for morning routines, departure countdowns, and upcoming events.

## Core Purpose

This dashboard helps families (particularly those with school-age children) by:
1. Displaying interactive checklists for each child's morning routine
2. Showing a countdown timer to bus/departure time
3. Presenting upcoming calendar events with countdown timers
4. Providing a glanceable, always-visible morning command center

## Target Use Case

- **Primary Display**: Large screen (TV, monitor, or tablet) in a common area (kitchen, hallway)
- **Primary Users**: Parents and children preparing for school/work
- **Usage Pattern**: Displayed automatically each morning, visible during breakfast and preparation time
- **Interaction Mode**: Touch-friendly for quick checklist interactions; minimal interaction needed for viewing

## Key Design Principles

### 1. Glanceability
- Information should be readable from across the room
- Large fonts, high contrast, clear visual hierarchy
- Critical information (time remaining) should be immediately visible

### 2. Simplicity
- No login required for daily viewing (single-family household)
- Admin/configuration mode is separate from daily display mode
- Children should be able to use checklists without assistance

### 3. Modularity
- Each dashboard section is an independent component
- New features can be added without affecting existing ones
- Components can be rearranged or disabled per family preference

### 4. Real-Time Updates
- Countdown timers update in real-time
- Checklist changes reflect immediately
- Time-sensitive information auto-refreshes

## Application Modes

### Display Mode (Default)
- Full-screen dashboard view
- Auto-refreshing content
- Touch-friendly checklist interactions
- No navigation or configuration options visible

### Admin Mode
- Accessed via specific URL or gesture/key combination
- Manage children and their routines
- Configure departure times and bus schedules
- Add/edit/remove calendar events
- Customize dashboard layout and appearance

## Technology Stack

| Component | Technology | Version |
|-----------|------------|---------|
| Framework | Laravel | 12.x |
| Frontend | Livewire + Volt | 3.x / 1.x |
| Styling | Tailwind CSS | 4.x |
| Database | SQLite | - |
| Testing | Pest | 4.x |
| PHP | PHP | 8.4 |

## Success Criteria

A successful implementation will:
1. Display all required information clearly on a single screen
2. Update countdowns in real-time without page refresh
3. Allow children to independently check off routine items
4. Provide parents with easy configuration options
5. Run reliably for extended periods without intervention
6. Support future feature additions through modular design

## Non-Goals (Current Scope)

- User authentication/multi-family support
- Mobile app version
- External calendar integration (future enhancement)
- Notification/alert sounds (future enhancement)
- Weather integration (future enhancement)

## Related Documentation

- [ARCHITECTURE.md](./ARCHITECTURE.md) - Technical architecture and patterns
- [FEATURES.md](./FEATURES.md) - Detailed feature specifications
- [DATA-MODELS.md](./DATA-MODELS.md) - Database schema and relationships
- [UI-COMPONENTS.md](./UI-COMPONENTS.md) - UI/UX guidelines and components
