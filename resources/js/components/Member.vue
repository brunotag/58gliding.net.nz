<template>
<div>
	<a class="btn btn-primary" v-if="showEdit" style="float:right; margin-left: 10px;" v-bind:href="'/members/' + results.id + '/edit'"><span class="fa fa-edit"></span> Edit Member</a>
	<a class="btn btn-outline-dark float-right ml-2" v-bind:href="'/members/' + results.id + '/ratings'"><span class="fa fa-clipboard-check"></span> Ratings</a>
	<a class="btn btn-outline-dark float-right ml-2" v-bind:href="'/members/' + results.id + '/achievements'"><span class="fa fa-trophy"></span> Achievements</a>
	<a class="btn btn-outline-dark float-right" v-bind:href="'/members/' + results.id + '/log'"><span class="fa fa-clipboard"></span> Change Log</a>

	<h1 class="results-title"><a href="/members">Members</a> &raquo; {{results.first_name}} {{results.last_name}}</h1>

	<div class="row">
		<div class="col-sm-6 col-xs-12">

			<table class="table table-striped">
				<tr>
					<th colspan="2">Member Details</th>
				</tr>
				<tr>
					<td class="table-label">Full Name</td>
					<td>{{results.first_name}} {{results.middle_name}} {{results.last_name}}</td>
				</tr>
				<tr>
					<td class="table-label">Address</td>
					<td>
						{{results.address_1}}<br v-if="results.address_1">
						{{results.address_2}}<br v-if="results.address_2">
						{{results.city}}<br v-if="results.city">
						{{results.country}} {{results.zip_post}}
					</td>
				</tr>
				<tr>
					<td class="table-label">Gender</td>
					<td>{{results.gender}}</td>
				</tr>
				<tr>
					<td class="table-label">Date of Birth</td>
					<td>{{results.date_of_birth}}</td>
				</tr>
				<tr>
					<td class="table-label">OO Number</td>
					<td>{{results.observer_number}}</td>
				</tr>
				<tr>
					<td class="table-label">Coach</td>
					<td>
						<span v-html="formatBoolean(results.coach)"></span>
					</td>
				</tr>
				<tr>
					<td class="table-label">Contest Pilot</td>
					<td>
						<span v-html="formatBoolean(results.contest_pilot)"></span>
					</td>
				</tr>
				<tr>
					<td class="table-label col-xs-6">Awards</td>
					<td>
						{{results.awards}}
					</td>
				</tr>
			</table>


			<table class="table table-striped">
				<tr>
					<th colspan="2">Contacts</th>
				</tr>
				<tr>
					<td class="table-label">Email</td>
					<td><a v-bind:href="'mailto:' + results.email">{{results.email}}</a></td>
				</tr>
				<tr>
					<td class="table-label">Home Phone</td>
					<td>{{results.home_phone}}</td>
				</tr>
				<tr>
					<td class="table-label">Mobile</td>
					<td>{{results.mobile_phone}}</td>
				</tr>
				<tr>
					<td class="table-label">Business Phone</td>
					<td>{{results.business_phone}}</td>
				</tr>
			</table>

			<table class="table table-striped">
				<tr>
					<th colspan="2">Comments</th>
				</tr>
				<tr>
					<td colspan="2">{{results.comments}}</td>
				</tr>
			</table>


		</div>



		
		<div class="col-sm-6 col-xs-12">


			<table class="table table-striped">
				<tr>
					<th colspan="2">GNZ Details</th>
				</tr>
				<tr>
					<td class="table-label">GNZ Number</td>
					<td>{{results.nzga_number}}</td>
				</tr>
				<tr>
					<td class="table-label">GNZ Member Type</td>
					<td>{{getMemberType(results.gnz_membertype_id).name}}</td>
				</tr>
				<tr>
					<td class="table-label">Created</td>
					<td>{{formatDateTime(results.created)}}</td>
				</tr>
				<tr>
					<td class="table-label">Modified</td>
					<td>{{formatDateTime(results.modified)}}</td>
				</tr>
				<tr>
					<td class="table-label">Primary Club</td>
					<td>{{results.club}}</td>
				</tr>
			</table>



			<table class="table table-striped">
				<tr>
					<th colspan="2">Membership Status & History</th>
				</tr>
				<template v-for="affiliate in orderedAffiliates">
					
					<tr>
						<td>{{affiliate.org.name}}</td>
						<td>

							
							<div>

								<span v-if="getMemberType(affiliate.membertype_id)">{{getMemberType(affiliate.membertype_id).name}}</span>
								
								<span v-if="!affiliate.resigned" class="success">Active</span>
								<span v-if="affiliate.resigned" class="error">Ended/Resigned</span>

								<br>
								From {{formatDate(affiliate.join_date)}} <span v-if="affiliate.end_date">to {{formatDate(affiliate.end_date)}}</span>
							</div>

						</td>
					</tr>
				</template>
			</table>






		</div>
	</div>
	</div>

</div>
</template>




<script>
	import common from '../mixins.js';

	export default {
		mixins: [common],
		props: ['memberId', 'showEdit'],
		data() {
			return {
				results: [],
				memberTypes: [],
				gnzMemberTypes: [],
				orgs: []
			}
		},
		mounted() {
			this.loadMember();
			this.loadMemberTypes();
			this.loadOrgs();
		},
		computed: {
			orderedAffiliates: function () {
				return _.orderBy(this.results.affiliates, ['join_date', 'resigned'], 'desc')
			}
		},
		methods: {
			getMemberType(membertypeId) {
				if (membertypeId==null) return {'id': null, 'name': 'None'};
				return this.memberTypes.find( ({ id }) => id === membertypeId);
			},
			loadOrgs: function() {
				var that = this;
				window.axios.get('/api/v1/orgs/').then(function (response) {
					that.orgs = response.data.data;
				});
			},
			loadMember: function() {
				var that=this;
				window.axios.get('/api/v1/members/' + this.memberId, {params: this.state}).then(function (response) {
					that.results = response.data.data;
				});
			},
			getMemberType(membertypeId) {
				if (membertypeId==null) return {'id': null, 'name': 'None'};
				return this.memberTypes.find( ({ id }) => id === membertypeId);
			},
			loadMemberTypes: function()
			{
				var that=this;
				window.axios.get('/api/v1/membertypes').then(function (response) {
					that.memberTypes = response.data.data;
				});
				window.axios.get('/api/v1/membertypes?org_id=' + 30).then(function (response) {
					that.gnzMemberTypes = response.data.data;
				});
			},
		}
	}
</script>