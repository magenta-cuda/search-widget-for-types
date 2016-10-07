<script type="text/html" id="st_iv-bs-template_table-lake">
<div class="table st_iv-table-base st_iv-table-lake">
<table class="st_iv-table-base st_iv-table-lake">
  <thead>
    <tr>
      <th>Lake</th>
      <th>Country</th>
      <th>Type</th>
      <th>Trophic</th>
      <th>Zone</th>
      <th>Area</th>
      <th>Depth</th>
      <th>Fishes</th>
    </tr>
  </thead>
  <tbody>
    {{{ data.items }}}
  </tbody>
</table>
</div>
</script>
<!-- Bootstrap Table Backbone Item Template -->
<script type="text/html" id="st_iv-bs-template_table_item-lake">
    <tr>
      <td><a href="<# print(stcfw.extractHrefAndLabelFromLink(data.post_title).href); #>" target="_blank"><# print(stcfw.extractHrefAndLabelFromLink(data.post_title).label); #></</a></td>
      <td><a href="<# print(stcfw.extractHrefAndLabelFromLink(data.country_id_of).href); #>" target="_blank"><# print(stcfw.extractHrefAndLabelFromLink(data.country_id_of).label); #></</a></td>
      <td>{{{ data["lake-type"] }}}</td>
      <td>{{{ data.trophic }}}</td>
      <td>{{{ data.zone }}}</td>
      <td class="st_iv-table-cell-right-align">{{{ data.area }}}</td>
      <td class="st_iv-table-cell-right-align">{{{ data.depth }}}</td>
      <td>{{{ data["river-fish"] }}}</td>
    </tr>
</script>

<script type="text/html" id="st_iv-bs-template_table-country">
<div class="table st_iv-table-base st_iv-table-country">
<table class="st_iv-table-base st_iv-table-country">
  <thead>
    <tr>
      <th>Country</th>
      <th>Mountains</th>
    </tr>
  </thead>
  <tbody>
    {{{ data.items }}}
  </tbody>
</table>
</div>
</script>
<!-- Bootstrap Table Backbone Item Template -->
<script type="text/html" id="st_iv-bs-template_table_item-country">
    <tr>
      <td><a href="<# print(stcfw.extractHrefAndLabelFromLink(data.post_title).href); #>" target="_blank"><# print(stcfw.extractHrefAndLabelFromLink(data.post_title).label); #></</a></td>
      <td>
      <!-- the expression below is equivalent to <td>{{{ data.mountain_id_for }}}</td> but is provided as an example of parsing fields with multiple links -->
      <# stcfw.extractHrefAndLabelFromLinks(data.mountain_id_for).forEach(function(link,i){
          print((i?", ":"")+'<a href="'+link.href+'" target="_blank">'+link.label+'</a>');
      }); #>
      </td>
    </tr>
</script>
