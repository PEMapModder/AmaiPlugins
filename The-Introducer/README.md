The-Introducer
==============

Spawns a client-side-only player entity who reminds him what to do. AI!!!!!

OK, for the fine description, this plugin spawns an AI player (bot) that is only visible to a target player. This serves as a player's assistant.

On the coding part, all bots use a common EID that is registeted on construction of thr plugin as a player entity outside the world margin.

The development priority of this project is `internals first UI second dev-friendly-support third`

Current lifestage:

1. Initializing plugin [done]
2. Create the Introducer class and register the EID and other interfaces on the PocketMine internals [working]
3. Complete the packets handling framework.
1. Make the AI for it to appear.
5. Fill in the actions of the bot.
6. Allow server-side customization of the bot.
7. Allow other plugins co-work with this plugin.
