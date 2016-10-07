<script type="text/html" id="st_iv-bs-template_table-lake">
<div class="table" style="overflow-x:auto;">
<table style="table-layout:auto;width:2000px;">
  <thead>
    <tr>
      <th>Lake</th>
      <th>Type</th>
      <th>Trophic</th>
      <th>Zone</th>
      <th>Fishes</th>
      <th>Excerpt</th>
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
      <td>{{{ data["lake-type"] }}}</td>
      <td>{{{ data.trophic }}}</td>
      <td>{{{ data.zone }}}</td>
      <td>{{{ data["river-fish"] }}}</td>
      <td>{{{ data.post_content }}}</td>
    </tr>
</script>
