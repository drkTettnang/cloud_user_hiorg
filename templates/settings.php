<div class="section">
	<h2>User Hiorg</h2>
	<form id="user_hiorg">
		<table style="width: 500px">
			<tr>
				<td><label for="ov">* Org.-Kürzel:</label></td>
				<td><input type="text" name="ov" id="ov" value="<?php p($_['ov']); ?>" /></td>
			</tr>
		</table>

		<div class="msg"></div>

		<input type="submit" value="Save settings" />
	</form>
</div>