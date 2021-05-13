<template>
<div>

	<h1>Manage Tiles &amp; Bluetooth Devices</h1>

	<div class="btn-group " role="group">
		<button type="button" class="btn btn-outline-dark" v-on:click="view='existing'; loadTiles();" v-bind:class="[ view=='existing' ? 'btn-secondary': 'btn-outline-dark' ]">Existing</button>
		<button type="button" class="btn btn-outline-dark" v-on:click="view='new'; loadTiles();" v-bind:class="[ view=='new' ? 'btn-secondary': 'btn-outline-dark' ]">Find New</button>
	</div>

	<p v-if="view=='new'">
		Note each Pi Tracker scans for bluetooth devices every 90 seconds, so this list can take a while to update.<br>
		Procedure to identify a tile:
		<ol>
			<li>Turn on tracker, give it a minute to get online</li>
			<li>Position tile very close to the tracker</li>
			<li>Ensure other tiles (e.g. in pockets) are moved away</li>
			<li>Wait for this page to update, maybe up to 2 minutes. Signal strength should around -30 when positioned very close.</li>
			<li>Set that tile to the name of the pilot who will usue that tile.</li>
			<li>For easy tile identifaction later, write or label the first and last digits of the bluetooth ID on the back e.g. 74:ee:5f:9a:c4:50 label as "74..50"</li>
		</ol>

	</p>

	<table class="table table-striped collapsable">
		<tr>
			<th>Bluetooth ID</th>
			<th>Last Seen</th>
			<th>Signal Strength</th>
			<th>Member</th>
			<th>Note</th>
			<th class="center">Edit</th>
		</tr>
		<tr v-for="tile in tiles">
			<td>{{tile.hex}}</td>
			<td>{{shortDateToNow(createDateFromMysql(tile.last_seen))}} from <span :title="'Device ID: ' + tile.last_device_id">{{tile.last_aircraft.rego}}</span> <small class="text-muted">{{tile.last_device_id}}</small></td>
			<td>
				<span v-if="tile.last_strength">
					{{tile.last_strength}}
					<span v-html="strength(tile.last_strength)"></span>
				</span>
				<span v-if="!tile.last_strength">n/a</span>
			</td>
			<td>
				<div v-if="tile.editing">
					<member-selector v-model="tile.member_id" class="mb-2"></member-selector>
					<button type="button" class="btn btn-outline-dark btn-sm" v-on:click="tile.member_id=null; tile.member=null; updateTile(tile)">Remove Member</button>
				</div>
				
				<span v-if="!tile.editing">
					<span v-if="tile.member">{{tile.member.first_name}} {{tile.member.last_name}}</span>
					<span v-if="!tile.member && tile.member_id!=null">#{{tile.member_id}} (name loading soon...)</span>
				</span>
			</td>
			<td>
				<input v-if="tile.editing" v-model="tile.note" type="text" class="form-control">
				<span v-if="!tile.editing">{{tile.note}}</span>
			</td>
			<td class="center">
				
				<div class="btn-group mb-0 " role="group">
					<button v-if="!tile.editing" type="button" class="btn btn-sm btn-outline-dark" v-on:click="editTile(tile)">Edit</button>
					<button v-if="tile.editing" type="button" class="btn btn-sm btn-primary" v-on:click="updateTile(tile)">Save Changes</button>
					<button v-if="tile.editing" type="button" class="btn btn-sm btn-outline-dark" v-on:click="cancelEditingTile(tile)">Cancel</button>
				</div>
			</td>
		</tr>
	</table>

</div>
</template>


<script>
	import common from '../../mixins.js';
	import vueDebounce from 'vue-debounce'
	Vue.use(vueDebounce);

	export default {
		mixins: [common],
		props: [],
		data() {
			return {
				tiles: [],
				view: 'existing', // existing or new
				editing: 0, // view or edit
			}
		},
		mounted() {
			this.timerLoop();
		},
		methods: {
			editTile: function(tile)
			{
				Vue.set(tile, 'editing', true);
				this.editing++;
			},
			cancelEditingTile: function(tile)
			{
				tile.editing = false;
				this.editing--;
			},
			timerLoop: function() {
				if (this.editing==0) { // only load if we are NOT editing anything
					this.loadTiles();
				}
				this.timeoutTimer = setTimeout(this.timerLoop, 5000); // 5 seconds
			},
			loadTiles: function() {
				var that=this;
				var url = '/api/v1/tiles/';
				if (this.view=='new') url = url + '?new=true&existing=false';
				window.axios.get(url).then(function (response) {
					that.tiles = response.data.data;
				});
			},
			updateTile: function(tile) {
				this.editing--;
				tile.editing = false;
				var url = '/api/v1/tiles/' + tile.id;
				window.axios.put(url, tile).then(function (response) {
					messages.$emit('success', 'Tile Updated');
				});
			}
		}
	}
</script>