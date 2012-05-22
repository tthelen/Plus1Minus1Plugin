jQuery(function() {
     jQuery('a.plus1minus1vote').live('click', function () {
          $(this).parent().load(
              '<?= PluginEngine::getURL('Plus1Minus1Plugin/vote') ?>',
              { vote_id: $(this).attr('data-voteid'), vote: $(this).attr('data-vote'), markup: $(this).attr('data-markup') });
          return false;
      });

      // jQuery('#plus1minus1listvotes img[title]').tooltip();
});

