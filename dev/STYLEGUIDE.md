# PartyMinder Style Guide

This style guide defines how PartyMinder looks, feels, and speaks. It ensures consistency across code, UI, documentation, and marketing. Update as we refine.

---

## 1. Voice & Tone

- **Human, not corporate**: Write like a trusted friend, not a platform.  
- **Optimistic, not hype**: Emphasize connection, trust, and real life. Avoid buzzwords.  
- **Simple, not jargon**: If a normal person wouldn’t say it at a dinner party, don’t write it.  

### Vocabulary Rules
- Always use **Conversations, Communities, Events** as the three pillars.  
- Use **Message** for a unit inside a conversation. Never “post,” “tweet,” or “status.”  
- Use **Circles** to describe trust levels. Label them as:  
  - **Circle 1 — Close**  
  - **Circle 2 — Trusted**  
  - **Circle 3 — Extended**  
- Action verbs:  
  - **Start a Conversation** (not “create a thread”)  
  - **Send a Message** (not “post”)  
  - **Join a Community** (not “subscribe”)  
  - **RSVP** (not “respond”)  
  - **Invite** (not “share”)  

---

## 2. Visual Identity

### Colorway (Draft)
- **Primary (Brand)**: Indigo Purple `#4B0082`  
- **Secondary (Accent)**: Warm Pink `#E6739F`  
- **Background / Neutral**: Soft Beige `#F9F5F0`  
- **Text / Support**: Soft Charcoal `#444444`  
- **Success**: Emerald Green `#2E8B57`  
- **Error / Alert**: Crimson `#DC143C`  

### Typography
- **Headings**: *Poppins Bold* or *Montserrat Bold*  
- **Body Text**: *Inter Regular* or *Open Sans*  
- **Buttons / UI Labels**: *Poppins Medium*  

Rules:  
- Headings: Sentence case (not all caps).  
- Body: Short paragraphs, max 2–3 sentences.  
- Links: Underlined by default.  

---

## 3. UI Components

- **Buttons**: Rounded corners (8–12px). Solid fills for primary actions, outline for secondary.  
- **Cards/Sections**: Minimal shadow, soft rounded edges. Consistent padding (16–24px).  
- **Forms**: Vertical layout, labels above inputs. Use brand colors only for accents, not for text fields.  
- **Avatars & Banners**: Circular avatars, wide rectangular banners. Always allow easy upload/change.  

---

## 4. Copywriting Do’s & Don’ts

**Do**  
- Say: “Send a Message to your Circle.”  
- Say: “This event is visible to your Trusted Circle.”  
- Say: “Join Conversations that matter.”  

**Don’t**  
- Don’t use “post,” “tweet,” or “status.”  
- Don’t call Circles “layers” or “degrees of separation.”  
- Don’t use “followers” or “friends” in system copy — use **Connections**.  

---

## 5. Code & CSS Naming

- Prefix all classes with `.pm-` (e.g., `.pm-button`, `.pm-card`).  
- Layout templates: `main`, `two-column`, `form`.  
- Responsive: mobile-first, then extend with media queries.  
- Use CSS variables for colors and typography:  
  ```css
  :root {
    --pm-primary: #4B0082;
    --pm-secondary: #E6739F;
    --pm-bg: #F9F5F0;
    --pm-text: #444444;
  }

## 6. Asset Guidelines

- Icons: Simple line icons, consistent stroke weight. Prefer open-source sets.

- Images: Encourage authentic event/community photos. Avoid stock imagery.

- Logos: Keep clear space equal to the “P” in PartyMinder on all sides.

## 7. Accessibility

- Maintain AA contrast ratio for all text.

- Provide alt text for images.

- Don’t rely on color alone for meaning (e.g., use icons with error/success states).

## 8. Tagline

PartyMinder: An Actually Social Network. Plan real events. Connect with real people. Share real life.

## 9. Branding in Code Comments

Every top-level CSS and JavaScript file should begin with a standard header comment. This ensures consistency and reinforces PartyMinder’s brand values across the codebase.

Template
/**
 * ======================================================
 *  PartyMinder – An Actually Social Network
 *  Plan real events. Connect with real people. Share real life.
 * ======================================================
 *
 *  File: [filename.css or filename.js]
 *  Description: [short description of what this file handles]
 *  Author: PartyMinder Team
 *
 *  Branding Notes:
 *  - Voice: Human, optimistic, simple
 *  - Vocabulary: Conversations, Communities, Events, Circles, Messages
 *  - Never use: posts, tweets, status, followers, degrees of separation
 *
 *  Colorway (as CSS variables):
 *    --pm-primary:   #4B0082;   /* Indigo Purple */
 *    --pm-secondary: #E6739F;   /* Warm Pink */
 *    --pm-bg:        #F9F5F0;   /* Soft Beige */
 *    --pm-text:      #444444;   /* Soft Charcoal */
 *
 *  Typography:
 *    Headings: Poppins Bold / Montserrat Bold
 *    Body: Inter Regular / Open Sans
 *    Buttons: Poppins Medium
 *
 *  Accessibility:
 *    Maintain AA contrast ratios.
 *    Provide alt text for images (in markup).
 *    Don’t rely on color alone for meaning.
 *
 * ======================================================
 */

Example – partyminder.css
/**
 * ======================================================
 *  PartyMinder – An Actually Social Network
 *  Plan real events. Connect with real people. Share real life.
 * ======================================================
 *
 *  File: partyminder.css
 *  Description: Global styles for PartyMinder plugin UI
 *  Author: PartyMinder Team
 *
 *  Branding Notes:
 *  - Use .pm- prefixes for all classes
 *  - Consistent spacing, rounded corners
 *  - Trust-centric vocabulary only
 *
 * ======================================================
 */
