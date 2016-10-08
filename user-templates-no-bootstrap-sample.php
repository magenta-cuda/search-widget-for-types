<!-- This is a sample user-templates.php file for the old Backbone with no Bootstrap mode.                       -->
<!-- This sample uses inline style for simplicity. You can also style it by creating a css/user_styles.css file. -->

<script type="text/html" id="stcfw-template-container-engine-table_view">
<div style="overflow-x:auto;">
<table style="width:2000px;table-layout:auto;">
<thead>
<tr>
<th>Post</th>
<th>Manufacturer</th>
<th>Carburetor</th>
<th>Car</th>
<th>Engine Type</th>
<th>Displacement</th>
<th>Horsepower</th>
<th>Excerpt</th>
</tr>
</thead>
<tbody class="stcfw-results-item-container stcfw-template-container-engine-table_view">
</tbody>
</table>
</div>
</script> 

<script type="text/html" id="stcfw-template-item-engine-table_view">
<tr>
<td>{{{ data.post_title }}}</td>
<td>{{{ data.manufacturer_id_of }}}</td>
<!-- the car_id_of field can be displayed using just "{{{ data.car_id_of }}}", below is an example of parsing the car_id_of field into its components -->
<td><a href="<# print(stcfw.extractHrefAndLabelFromLink(data.car_id_of).href); #>"><# print(stcfw.extractHrefAndLabelFromLink(data.car_id_of).label); #></a></td>
<td>{{{ data.carburetor_id_for }}}</td>
<td>{{{ data.engine_type }}}</td>
<td>{{{ data.displacement }}}</td>
<td>{{{ data.horsepower }}}</td>
<td>{{{ data.post_content }}}</td>
</tr>
</script> 
