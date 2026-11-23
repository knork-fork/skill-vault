# How to Use Environment Variables

Environment variables let you keep values such as IDs, URLs or names out of skill text and refer to them by placeholder instead.

## Add a variable

1. Open **Settings > Environment Variables**.
2. Click **+** in the table header (or **Add variable** if you have none yet).
3. Enter a **placeholder** and a **value**, then click **Save**.

Placeholders must start with a letter or underscore, contain only letters, digits and underscores, and be unique to you (e.g. `TRELLO_BOARD_ID`). Values cannot be empty. Use the pencil button on a row to edit it and the **✕** button to delete it.

## Set a variable from a skill

Open a skill: placeholders in its **Content** tab are **bold red** if you haven't set them and **blue** if you have (hover to see the value). Click a red one to jump to **Environment Variables** with its name filled in, then type the value and click **Save**.

Enabled skills with unset placeholders are flagged with a red warning icon on the **Skills** nav item and in the skills list (hover for the missing names).

## Use a variable in a skill

Write the placeholder in the skill body wrapped in double curly braces:

```
Fetch the cards from board {{TRELLO_BOARD_ID}} and summarise them.
```

When the skill is used, `{{TRELLO_BOARD_ID}}` is replaced with the value you saved.

## Good to know

- Variables are personal: each user has their own set, and nobody else sees yours.
- Values are stored as plain text, so don't use them for secrets.
