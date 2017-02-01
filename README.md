# Customize Edit Post Flow

This is a plugin to prototype a Customizer → Post Editor → Customizer flow for WordPress core.

See [Trac ticket #39752](https://core.trac.wordpress.org/ticket/39752) for ongoing discussion.

## What is it?

The Customizer has always prevented following any non-frontend links in the preview, which has disabled post links provided by `edit_post_link()` from working. This was worked around in [#38648](https://core.trac.wordpress.org/ticket/38648) by no-op'ing the edit links.

One approach to solving this problem would be to bring post editing into the Customizer, which has been explored: https://github.com/xwp/wp-customize-posts

Another approach would be:

1. Navigate to the appropriate edit post screen when an edit_post_link() is clicked on
2. Before 1), save a Customize changeset if the state is unsaved
3. Provide a UI on the edit posts screen to return to the Customizer after editing is complete
4. When returning to the Customizer, restore the changeset stored in 2) if one was needed.

This would solve the problems of there being no way to edit posts from the Customizer, while not trying to stuff a (duplicate) post editing UI into a space that is not well-suited to it.

This could also help to editing posts created in nav menus [#38002](https://core.trac.wordpress.org/ticket/38002)

## Minimally viable flow

This is about providing a minimally viable flow where there is none now, where that flow is better than having none at all, is is the case right now. Many [enhancements](https://core.trac.wordpress.org/ticket/39752#comment:1) to it could be made, if this is found valuable. But it will probably be better than nothing, giving users another path to customizing what they see in their Customizer preview.