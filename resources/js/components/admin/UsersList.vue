<template>
<div>
	<div class="btn-group pull-right " role="group">
		<input type="text" class="form-control" v-model="q" v-on:keyup="search()" size="30" placeholder="Search">
	</div>

	<div class="btn-group" role="group">
		<button type="button" class="btn btn-outline-dark btn-sm" v-on:click="previous()">&lt;</button>
		<button type="button" class="btn btn-outline-dark btn-sm disabled">Page {{ state.page }} of {{ last_page }}</button>
		<button type="button" class="btn btn-outline-dark btn-sm" v-on:click="next()">Next &gt;</button>
	</div>

	<div class="btn-group  mr-2" role="group">
		<button type="button" class="btn btn-outline-dark btn-sm disabled">Filter to </button>
		<button class="btn btn-outline-dark btn-sm" v-on:click="selectRole(role)" v-for="role in roles" v-bind:class="[ selectedRole==role.id ? 'btn-secondary': 'btn-outline-dark' ]">
			{{role.name}}
		</button>
	</div>

	<table class="table table-striped results-table top-margin">
		<tr>
			<th class="clickable" v-on:click="order('email')">Email</th>
			<th class="clickable" v-on:click="order('first_name')">First</th>
			<th class="clickable" v-on:click="order('last_name')">Last</th>
			<th class="clickable center" v-on:click="order('activated')">Active</th>
			<th class="clickable center" v-on:click="order('gnz_active')">GNZ ID Validated</th>
			<th class="clickable center" v-on:click="order('gnz_id')">GNZ ID</th>
			<th v-if="selectedRole">Org</th>
			<th class="center" v-if="showEdit">Edit</th>
		</tr>
		<tr v-for="result in results">
			<td>{{ result.email }}</td>
			<td>{{ result.first_name }}</td>
			<td>{{ result.last_name }}</td>
			<td class="center">
				<i v-show="result.activated" class="fa fa-check success"></i>
				<i v-show="!result.activated" class="fa fa-times error"></i>
			</td>
			<td class="center">
				<i v-show="result.gnz_active" class="fa fa-check success"></i>
				<i v-show="!result.gnz_active" class="fa fa-times error"></i>
			</td>
			<td class="center">{{ result.gnz_id }}</td>
			<td v-if="selectedRole">{{ result.name }}</td>
			<td class="center" v-if="showEdit"><a v-bind:href="'/admin/users/' + result.id + '/roles'" class="btn btn-primary btn-xs">Roles</a></td>
		</tr>
	</table>

	<div class="btn-group" role="group">
		<button type="button" class="btn btn-outline-dark btn-sm" v-on:click="previous()">&lt;</button>
		<button type="button" class="btn btn-outline-dark btn-sm disabled">Page {{ state.page }} of {{ last_page }}</button>
		<button type="button" class="btn btn-outline-dark btn-sm" v-on:click="next()">Next &gt;</button>
	</div>
</div>
</template>

<script>
import common from '../../mixins.js';
export default {
	mixins: [common],
	data: function() {
		return {
			results: [],
			orgs: [],
			roles: [],
			selectedOrg: "all",
			sort: 'email',
			direction: 'asc',
			state: {page: 1},
			last_page: 0,
			total: 0,
			q: '',
			showEdit: false,
			selectedRole: null
		}
	},
	watch: {
		'state': {
			handler: 'load',
			deep: true
		}
	},
	created: function() {
		this.load();
		this.loadRoles();
		if (window.Laravel.clubAdmin) this.showEdit=true;
	},
	methods: {
		selectRole: function(role)
		{
			if (this.selectedRole==role.id) this.selectedRole=null;
			else {
				this.selectedRole=role.id;
				this.state.page = 1;
			}

			this.load();

		},
		order: function(sortBy) {
			// check if we should change the direction
			if (this.sort==sortBy) {
				if (this.direction=='asc') this.direction='desc';
				else this.direction='asc';
			}
			// set the new item to sort by
			this.sort = sortBy;
			this.state.page=1;
			this.load();
		},
		loadRoles: function() {
			var that = this;
			window.axios.get('/api/v1/roles').then(function (response) {
				var responseJson = response.data;
				that.roles = responseJson.data;
			});	

		},
		load: function() {
			// this.$http.get('/api/v1/orgs').then(function (response) {
			// 	this.orgs = response.data.data;
			// });
			var that = this;
			var data = {sort: this.sort, direction: this.direction, page: this.state.page, q: this.q, role: this.selectedRole};
			window.axios.get('/api/v1/users', {params: data}).then(function (response) {
				var responseJson = response.data;

				that.results = responseJson.data;
				that.last_page = responseJson.last_page;
				that.total = responseJson.total;
				if (that.page > that.last_page && that.last_page>0) {
					that.page = 1;
				}
			});
		},
		search: function() {
			this.state.page=1;
			this.load();
		}
	}
}
</script>
