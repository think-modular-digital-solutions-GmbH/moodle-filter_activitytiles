# Activity Tiles text filter

This text filter plugin can display beautiful tiles for all of your activities anywhere in a course, most likely in a text label.

## Installation

Install like any other filter plugin - put into your /filter subfolder and enable in site filter settings.

Note: if you want to use this plugin inside of blocks, you have to turn it ON for the whole site, since you cannot selectively turn on specific filters for blocks.

## Usage

Just enter **{activitytiles}** in any text to render the activitytiles there.

### Selecting specific activities / activity types

Optionally, you can only display a specific type of activities, for example **{activitytiles:assign}** will only display assignments.

If you only want to display, you can do this with **{activitytiles:selected}**. This will only display activities, that have the "Selected for tiles" checkbox set in the **Activity Tiles Settings** section of the activity's settings.

### Style

There, you can also optionally set a fontawesome icon or image for each activity, to make your tiles look even more beautiful.

If no image is set, fontawesome icon will be used, if neither is set, the default activity type icon will be used.

When activity completion is activated, completion information will automatically be displayed on the activity tile.
