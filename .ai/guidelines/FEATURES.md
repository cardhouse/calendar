# Feature Specifications

## Overview

This document provides detailed specifications for each feature of the Family Morning Dashboard. Each feature section includes requirements, behavior descriptions, and acceptance criteria.

---

## Feature 1: Morning Routine Checklists

### Description

Interactive checklists for each child displaying their morning routine tasks. Children can tap/click items to mark them complete, providing visual feedback on their progress.

### Requirements

#### 1.1 Child Profiles

- Support multiple children (2-6 typical)
- Each child has:
  - Name (displayed prominently)
  - Avatar color (for visual distinction)
  - Display order (customizable)
  - Individual routine items

#### 1.2 Routine Items

- Each routine item has:
  - Name (e.g., "Brush teeth", "Make bed", "Eat breakfast")
  - Display order within child's list
  - Completion status (resets daily)
  - Optional icon (future enhancement)

#### 1.3 Checklist Behavior

- Items display with checkbox/indicator
- Tapping an item toggles completion status
- Completed items show visual distinction (strikethrough, checkmark, color change)
- Completion persists until daily reset
- Daily reset occurs at configurable time (default: midnight)

#### 1.4 Progress Indicator

- Each child's section shows overall progress
- Visual indicator (progress bar or X of Y completed)
- Celebration state when all items complete (subtle animation or color)

### Acceptance Criteria

- [ ] Multiple children can be displayed simultaneously
- [ ] Each child's routine items are independent
- [ ] Tapping an item toggles completion immediately (no page reload)
- [ ] Completed items are visually distinct from incomplete
- [ ] Progress updates in real-time as items are checked
- [ ] Checklist state persists across page refreshes
- [ ] Daily reset clears all completion statuses

### Admin Configuration

- Add/edit/remove children
- Add/edit/remove routine items per child
- Reorder children and items via drag-and-drop or order input
- Set avatar colors per child
- Configure daily reset time

---

## Feature 2: Departure Countdown Timer

### Description

A prominent countdown timer showing time remaining until the bus arrives or the family needs to leave. Changes visual state as departure approaches.

### Requirements

#### 2.1 Timer Display

- Large, easily readable countdown format
- Shows hours, minutes, and seconds (or just minutes:seconds when under 1 hour)
- Updates in real-time (every second)
- Displays associated label (e.g., "Bus arrives in", "Leave for school in")

#### 2.2 Multiple Departure Times

- Support multiple departure times (e.g., different buses, different days)
- System automatically shows the next relevant departure time
- Ability to configure which days each departure applies (weekdays, specific days)

#### 2.3 Visual States

| State | Condition | Visual Treatment |
|-------|-----------|------------------|
| Normal | > 30 minutes remaining | Standard display, calm color |
| Approaching | 15-30 minutes remaining | Yellow/warning color |
| Urgent | 5-15 minutes remaining | Orange color, possibly larger |
| Critical | < 5 minutes remaining | Red color, high emphasis |
| Passed | Departure time passed | Different message, muted color |

#### 2.4 Configurable Thresholds

- Allow customization of time thresholds for each visual state
- Different departure times can have different thresholds

### Acceptance Criteria

- [ ] Timer counts down accurately in real-time
- [ ] Timer automatically selects the next relevant departure time
- [ ] Visual state changes based on remaining time
- [ ] Timer handles day transitions correctly
- [ ] Non-applicable departure times (wrong day) are skipped
- [ ] Passed departure times show appropriate message

### Admin Configuration

- Add/edit/remove departure times
- Set time for each departure
- Set label/name for each departure
- Configure applicable days (weekdays, weekends, specific days)
- Adjust visual state thresholds (optional)

---

## Feature 3: Upcoming Events Countdown

### Description

A section displaying the next 3 upcoming calendar events with countdown timers showing time remaining until each event.

### Requirements

#### 3.1 Event Display

- Show the 3 soonest upcoming events
- Each event displays:
  - Event name
  - Countdown (days, hours format: "2 days, 4 hours")
  - Event date/time
  - Optional: category/color

#### 3.2 Countdown Format

| Time Remaining | Format |
|----------------|--------|
| > 7 days | "X days" |
| 2-7 days | "X days, Y hours" |
| 1-2 days | "Tomorrow" or "1 day, Y hours" |
| < 24 hours | "X hours, Y minutes" |
| < 1 hour | "X minutes" |
| Event started | "Now" or "In progress" |

#### 3.3 Event Sorting

- Events sorted by start date/time ascending
- Past events automatically removed from display
- If fewer than 3 upcoming events, show what's available

#### 3.4 Event Categories (Optional Enhancement)

- Birthdays
- School events
- Family activities
- Appointments
- Custom categories

### Acceptance Criteria

- [ ] Next 3 upcoming events are displayed
- [ ] Countdown timers update periodically (every minute is sufficient)
- [ ] Past events are automatically hidden
- [ ] Events display in chronological order
- [ ] Empty state handled gracefully when no events exist
- [ ] Event countdowns are accurate across time zones

### Admin Configuration

- Add new calendar events
- Edit existing events
- Delete events
- Set event name, date, time
- Set optional category/color
- View list of all events (including past)

---

## Feature 4: Dashboard Display

### Description

The main display page that combines all components into a cohesive, glanceable morning dashboard.

### Requirements

#### 4.1 Layout

- Single-page display (no scrolling required on target display)
- Responsive to different screen sizes
- Clear visual hierarchy with sections for each feature
- Clock/current time always visible

#### 4.2 Header Section

- Current time (large, prominent)
- Current date
- Day of week
- Optional: greeting or family name

#### 4.3 Content Sections

```
+--------------------------------------------------+
|                    HEADER                         |
|              8:15 AM - Monday, Jan 6              |
+--------------------------------------------------+
|                                                   |
|   +-------------+  +-------------+                |
|   |   CHILD 1   |  |   CHILD 2   |    DEPARTURE  |
|   |  CHECKLIST  |  |  CHECKLIST  |     TIMER     |
|   |             |  |             |               |
|   |  [ ] Item   |  |  [ ] Item   |    23:45      |
|   |  [x] Item   |  |  [ ] Item   |   until bus   |
|   |  [ ] Item   |  |  [x] Item   |               |
|   +-------------+  +-------------+                |
|                                                   |
+--------------------------------------------------+
|              UPCOMING EVENTS                      |
|  +------------+ +------------+ +------------+     |
|  | Birthday   | | Soccer     | | Dentist    |     |
|  | 2d 4h      | | 5d 12h     | | 1w 2d      |     |
|  +------------+ +------------+ +------------+     |
+--------------------------------------------------+
```

#### 4.4 Auto-Refresh

- Dashboard automatically updates without manual refresh
- Use Livewire polling for time-sensitive elements
- Minimize unnecessary re-renders

#### 4.5 Display Modes

- **Normal Mode**: Full dashboard with all sections
- **Fullscreen Mode**: Hints for entering browser fullscreen, optimized for kiosk display
- **Night Mode** (Future): Dimmed display during non-morning hours

### Acceptance Criteria

- [ ] All sections visible without scrolling on 1080p display
- [ ] Current time updates every second
- [ ] All sections are clearly distinguishable
- [ ] Dashboard works on various screen sizes
- [ ] No manual refresh needed for updates

---

## Feature 5: Admin Configuration Panel

### Description

A separate administrative interface for managing all dashboard configuration.

### Requirements

#### 5.1 Access

- Accessed via `/admin` URL
- Optionally protected by PIN code (future enhancement)
- Clear navigation between admin sections

#### 5.2 Admin Sections

| Section | Purpose |
|---------|---------|
| Children | Add/edit/remove child profiles |
| Routines | Manage routine items per child |
| Departures | Configure departure times |
| Events | Manage calendar events |
| Settings | General dashboard settings (future) |

#### 5.3 CRUD Operations

All admin sections support:
- Create new items
- Read/list existing items
- Update item details
- Delete items (with confirmation)

#### 5.4 User Experience

- Changes take effect immediately on dashboard
- Form validation with clear error messages
- Success feedback on save operations
- Confirmation dialogs for destructive actions

### Acceptance Criteria

- [ ] All CRUD operations work for each entity type
- [ ] Form validation prevents invalid data
- [ ] Changes reflect immediately on main dashboard
- [ ] Delete operations require confirmation
- [ ] Navigation between admin sections is intuitive

---

## Feature Priority

For initial implementation, features should be built in this order:

1. **Data Models & Migrations** - Foundation for all features
2. **Dashboard Display Layout** - Basic structure and styling
3. **Departure Countdown Timer** - High-impact, standalone feature
4. **Morning Routine Checklists** - Core feature with interactivity
5. **Upcoming Events Countdown** - Completes main display
6. **Admin Configuration Panel** - Enables customization

---

## Future Enhancements

These features are out of scope for initial implementation but should be considered in architecture:

- **Weather Integration**: Display current weather and forecast
- **Google Calendar Sync**: Import events from external calendars
- **Audio Alerts**: Sound notifications at key times
- **Multiple Households**: Authentication and family-specific data
- **Custom Widgets**: User-created dashboard sections
- **Mobile Companion App**: Remote checklist completion
- **Theme Customization**: Colors, fonts, layout options
- **Recurring Events**: Support for repeating calendar events
- **Routine Templates**: Pre-built routine sets to choose from

## Related Documentation

- [PROJECT.md](./PROJECT.md) - Project overview
- [ARCHITECTURE.md](./ARCHITECTURE.md) - Technical architecture
- [DATA-MODELS.md](./DATA-MODELS.md) - Database schema
- [UI-COMPONENTS.md](./UI-COMPONENTS.md) - UI guidelines
