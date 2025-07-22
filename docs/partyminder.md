# Party Minder

How, exactly, is this going to make any money?


## Actions

### Next

Get an explanation of this.
     // Prevent direct access
     if (!defined('ABSPATH')) {
         exit;
     }

Add a choice to use an existing DID.

Members need to be able to invite guests while creating, viewing, and editing an event.

I would like to be able to delete events both in the admin backend and as the event creator in
the front end.

Let's build a community network with event planning mvp for now. Leave the gates open for restaurant and global features. But get this developed and on a website today.

Start Simple, Scale Smart:

Implement AT Protocol DIDs but begin with single-site communities
Build the permission system (member > community hierarchy) from day one
Design database schema for global scale but populate locally first
Community DIDs immediately, but cross-site discovery comes in v2

MVP Member Flow:

Member joins with DID (future-proofs their identity)
Creates/joins local communities first
Foundation exists for cross-site expansion when ready










### Backlog

Have the plug in create an instructions page that the site admin can include in their own navigation.

Build a community management system. Members can create communities. Members can
add other members to their communities. Members communitites can overlap.
Communities have community permissions that do not override member permissions.
Inner circle, friends, work, church, global, etc. Communities can span sites.
Members can discover members on other sites. Connections are persistent.

Conversation topics. Configure and adjust the default conversation topics.

Provide an option for newest first and set as default.

In local, change Local Sites to local-sites.

Review css again. Ensure only necessary inline style. All other style goes
in the style system.

## Claude Code Instructions
Add instructions for handling style. Ensure only necessary inline style. All other style goes in the style system.


## Admin Instructions

Add shortcode list and instructions.

Add instrucitons for styling pages.

Add instructions for setting up navigation.

We provide a set of default pages and shortcodes you can use throughout your site.
These pages can be styled using your theme's style tools. For example, I suggest
using the "page no title" page template.




## Profile Page
  **User Profile Features:**  


Yes. The profile page we discussed earlier. What questions do we need to answer before we create the page? What risks do we face creating the page without sufficient planning? 

I imagine our selection of fields will be fairly easy to manage and update/change as we go forward. 

I am not clear on how we should manage membership. You mentioned using the wordpress profile system or our own. I want to offer AT Proto integration either now or as an addon in the future.


For sure we want Name, Avatar, Profile image, Bio, preferences (food),
allergies (private), notification settings, social links, location, 

Do we want to use the built in wordpress page with column?

The profile page should only be for registered members. It should be
required to create an account to use our service.

Everyone that uses the site needs an account. Invitations include a link to account creation if the invitee does not have an account already.  
How do we use the at protocol?

I think we need our own profile system? Of course we will need to use wordpress authentication but I would like to use our own stylized login form. Or use a theme based login form. How do we integrate AT Proto?

I agree we should have a profile page, a main page, 

I do not want to use the word "discussion". Nor "discussion group". They
sound to much like work. I'm open to suggestions and ready to use
"conversation" for now.

## Language
Community

Member

Host

Guest

Conversation

Comment




## Notes

   \- **Event categories/tags** (dinner parties, birthdays, etc.)  
Yes.   
Dinner party. A group of people for food. Private group.  
House party. A more open group of people for an evening or day at a home, boat, or beach. Friends can bring friends.  
Event. Could be multi-day. So arrangements for parking, housing, etc.  
  \- **RSVP management improvements** (dietary restrictions, plus-ones)  
  \- **Email notifications** system  
  \- **Event search/filtering** on the events page  
  What sounds most valuable for your users right now?

Maybe a main central section with the user/visitor social activity. We need to decide what to do with social activity. I do not want an algorithm driven "newsfeed". I want more of an ongoing set of discussions. Not real sure how to implement this yet.

You showed me there is a lot more to a user profile page than just creating a page. We need to discuss users. How we acquire them. How we manage them, etc.       

public and private? 

Preferences and allergies. Meat. Veggie, vegan, raw.

What would adding the social activities to the profile page look like?

For house parties, how do we share the invitation? Share to bsky? Facebook.


What is a meta box?

