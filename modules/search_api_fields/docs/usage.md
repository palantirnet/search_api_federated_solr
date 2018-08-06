## About this module

This module provides new field options on Search API indexes:

1. The "Mapped field" field can be used to aggregate data from different entity types into the same field in the search index.

    This is similar to the "Aggregated field" provided by Search API, but gives more direct, token-based control over the values for different entity types.
    
    See [Using the "Mapped field"](#using-the-mapped-field).
1. The "Mapped terms" field can be used to assign "mapped term" values to any taxonomy term entities within a given site.  See [Using the "Mapped terms" field](#using-the-mapped-terms-field).

## Using the "Mapped field"

1. Visit the fields list for your index at _Admin > Configuration > Search API > [your index] > Fields_ (path `/admin/config/search/search-api/index/YOUR_INDEX/fields`)
2. Click "Add fields"
3. Click the "Add" button for the "Mapped Field":

  <img src="images/add_mapped_field.png" />
  
4. Configure field data for each entity type. This field allows token replacement; enter plain text directly or use the token browser to select tokens.

  <img src="images/edit_mapped_field.png" />
  
5. Save your field.
6. Edit the field label, machine name, and type as necessary for your data

## Using the "Mapped terms" field

1. Visit the fields list for your index at _Admin > Configuration > Search API > [your index] > Fields_ (path `/admin/config/search/search-api/index/YOUR_INDEX/fields`)
1. Click "Add fields"
1. Click the "Add" button for the "Mapped terms":
    <img src="images/add_mapped_terms.png" /> 
1. Once you have added the "Mapped terms" field to your search index configuration, you can read through the provided instructions and save the field:
    <img src="images/confirmation_added_mapped_terms.png" />
1. Remember to save the index field configuration:
    <img src="images/save_index_field_config.png" /> 
1. Configuration for mapped terms happens within the taxonomy term entity edit UI itself.  Browse to a taxonomy vocabulary on your site and add an instance of the "Mapped terms" field type (If you plan on sharing this field among your vocabularies, use something like "Mapped terms" for the field label).
    <img src="images/add_mapped_terms_to_vocabulary.png" />
    <img src="images/add_mapped_term_field_label.png" />
1. Save the field settings
    <img src="images/add_mapped_field_settings_save.png" />
    <img src="images/add_mapped_field_save_settings_2.png" />
1. Edit any terms in the vocabularies to which you've just added a "Mapped terms" field instance.  On the term edit form, you should now see a "Mapped terms" field instance where you can add one or many "mapped" terms.
    <img src="images/add_mapped_term_to_term.png" />
1. Repeat for each term in each vocabulary which should have a mapped term value.
1. Once content which references these terms is indexed, all of their corresponding "mapped terms" will appear in the `mapped_terms` index property field.
