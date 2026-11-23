# How to Use Environment Variables

Environment variables let you keep values such as IDs, URLs or names out of skill text and refer to them by placeholder instead.

## Add a variable

1. Open **Settings > Environment Variables**.
2. Click **+** in the table header (or **Add variable** if you have none yet).
3. Enter a **placeholder** and a **value**, then click **Save**.

Placeholders must start with a letter or underscore, contain only letters, digits and underscores, and be unique to you (e.g. `TRELLO_BOARD_ID`). Values cannot be empty. Use the **✕** button on a row to delete a variable.

## Use a variable in a skill

Write the placeholder in the skill body wrapped in double curly braces:

```
Fetch the cards from board {{TRELLO_BOARD_ID}} and summarise them.
```

When the skill is used, `{{TRELLO_BOARD_ID}}` is replaced with the value you saved.

## Good to know

- Variables are personal: each user has their own set, and nobody else sees yours.
- Values are stored as plain text, so don't use them for secrets.
