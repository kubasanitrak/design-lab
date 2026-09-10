# Changelog

## [0.6.1]

- Pass and payment-recap pages: standalone title sections, bank-transfer layout tweaks.

## [0.6.0]

- Member accounts: checkout requires login or register-on-submit, verification e-mail → set password, role `dlab_member`.
- Member dashboard `/muj-ucet-design-lab/`: account settings (editable e-mail, phone, lost-password link), bookings overview, cancel whole order, reschedule workshop (pass pricing recalculated).

## [0.5.0]

- Phase 4: book a single workshop or a pass from `/pass/` via **Rezervovat**, checkout at `/rezervace/`, reservation holds, bank-transfer recap and Paylibo QR, emails, admin reservation list.

## [0.4.2]

- Pass page wrapped in theme dilna sections; detail sections use full-width layout.

## [0.4.1]

- Card actions bar and pass button styling; detail CTA/tags tweaks; public CSS recompiled.

## [0.4.0]

- Workshop detail rebuilt to match theme dilna sections (gallery, instructors, program table, sticky details); public CSS recompiled.

## [0.3.1]

- Workshop card: age badge on the image, date in meta, compiled public CSS.

## [0.3.0]

- Admin settings for checkout later phases: reservation hold/cancel windows, bank details for QR platba, invoice sequence (YY-#####), email sender and admin notifications.

## [0.2.0]

- Phase 2: guest + logged-in pass basket, shared attendee headcount, pass pricing from 2+ workshops, AJAX add/remove, occupancy hooks, `[dlab_pass]` recap at `/pass/`, header `[dlab_basket_count]`.

## [0.1.0]

- Phase 0 scaffold: bootstrap, Plugin Update Checker, activation pages, admin shell, i18n, SCSS, GitHub release zip.
- Phase 1: CPT `dlab_workshop`, taxonomies věk / obor, ACF field group, listing/detail shortcodes, theme-matching grid and detail.
