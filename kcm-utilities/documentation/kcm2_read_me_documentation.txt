Kcm2 standards:


file names:
kcm2sys-   system functions, classes, etc that will work in all environments, not only kcm2 -these functions do not use any kcm functions (
kcm2lib -  kcm2 functions
kcm2data - data classes
kcm2css  -
csskcm2_  - scripts

kcm2_start
kcm2_results
kcm2_setup
kcm1-rpt and kcm1*.php - mostly changes for new interface, most changes are in kcm1-libNavigate include code, but a few interface changes, and new location for css code (../ added)
kcm1 include code - modified from kcm1 to work in kcm2 - in general not many changes (if any) - ready for future changes if necessary
kcm1-libNavigate - many changes in this piece of code due to interface changes


standard variable name parts:
_filter_
_read_
_write_
_loadRow_

moniker - a key that is used in associative arrays. Example: $field[$fieldMoniker] - $field is an associative array, and $fieldMoniker is the id of the field

 
css standards:
no underlines used (so no naming conflicts with php)
at least one dash in each style name (so no naming conflicts with php)
