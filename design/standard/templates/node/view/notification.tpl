	<h3><a href="{$node.url_alias|ezurl('no', 'full')}">{$node.name}</a></h3>

	<table border="0">
		{foreach $node.data_map as $attribute}
			<tr style="padding:0;margin: 0;">
				<td style="text-align: right; font-weight: bold;font-size:1em;padding:0 5px 0 0;margin: 0; line-height:1.5;">{$attribute.contentclass_attribute_name} : </td>
				<td style="font-style: italic;font-size:1em;padding:0;margin: 0; line-height:1.5;">{attribute_view_gui attribute=$attribute}</td>
			</tr>
		{/foreach}
	</table>