<template>
<div>
	<h1 class="results-title"><a href="/members">Members</a> &raquo; <a :href="'/members/' + member.id">{{member.first_name}} {{member.last_name}}</a> &raquo; Edit</h1>

	<div class="row">
		<div class="col-sm-6 col-xs-12">

			<table class="table table-striped">
				<tr>
					<th colspan="2">Member Details</th>
				</tr>
				<tr>
					<td class="table-label col-xs-6">First Name</td>
					<td><input type="text" v-model="member.first_name" class="form-control"></td>
				</tr>
				<tr>
					<td class="table-label col-xs-6">Middle Name</td>
					<td><input type="text" v-model="member.middle_name" class="form-control"></td>
				</tr>
				<tr>
					<td class="table-label col-xs-6">Last Name</td>
					<td><input type="text" v-model="member.last_name" class="form-control"></td>
				</tr>
				<tr>
					<td class="table-label col-xs-6">Address</td>
					<td>
						<input type="text" v-model="member.address_1" class="form-control"><br>
						<input type="text" v-model="member.address_2" class="form-control">
					</td>
				</tr>
				<tr>
					<td class="table-label col-xs-6">City</td>
					<td><input type="text" v-model="member.city" class="form-control"></td>
				</tr>
				<tr>
					<td class="table-label col-xs-6">Country</td>
					<td><input type="text" v-model="member.country" class="form-control"></td>
				</tr>
				<tr>
					<td class="table-label col-xs-6">ZIP/Post Code</td>
					<td><input type="text" v-model="member.zip_post" class="form-control"></td>
				</tr>
				<tr>
					<td class="table-label col-xs-6">Gender</td>
					<td>
						<select v-model="member.gender" class="form-control">
							<option value="M">Male</option>
							<option value="F">Female</option>
						</select>
					</td>
				</tr>
				<tr>
					<td class="table-label col-xs-6">Date of Birth</td>
					<td>
						<!-- <input v-if="showAdmin" type="text" v-model="member.date_of_birth" class="form-control"> -->
						<v-date-picker  v-if="member.can_edit" id="start_date" v-model="member.date_of_birth" :locale="{ id: 'start_date', firstDayOfWeek: 2, masks: { weekdays: 'WW', L: 'DD/MM/YYYY' } }" :popover="{ visibility: 'click' }"></v-date-picker>
						<span v-if="!member.can_edit">
							{{member.date_of_birth}}
						</span>
					</td>
				</tr>
				<tr>
					<td class="table-label col-xs-6">OO Number</td>
					<td>
						<input type="text" v-model="member.observer_number" class="form-control" v-if="showAdmin">
						<span v-if="!showAdmin">{{member.observer_number}}</span>
					</td>
				</tr>
				<tr>
					<td class="table-label col-xs-6">Privacy</td>
					<td>
						<div class="checkbox">
							<label><input type="checkbox" v-model="member.privacy"> Keep contact details private from other GNZ members</label>
						</div>
					</td>
				</tr>
				<tr>
					<td class="table-label col-xs-6">Roles</td>
					<td>
						<div class="checkbox">
							<label><input type="checkbox" v-model="member.coach"> Coach</label>
							<br>
							<label><input type="checkbox" v-model="member.contest_pilot"> Contest Pilot</label>
						</div>
					</td>
				</tr>
				<tr>
					<td class="table-label col-xs-6">Awards</td>
					<td>
						<input type="text" v-model="member.awards" class="form-control" v-if="showAdmin">
						<span v-if="!showAdmin">{{member.awards}}</span>
					</td>
				</tr>
				<tr>
					<td></td>
					<td><button class="btn btn-primary btn-sm" v-on:click="saveMember()">Save Changes</button></td>
				</tr>
			</table>


			<table class="table table-striped">
				<tr>
					<th colspan="2">Contacts</th>
				</tr>
				<tr>
					<td class="table-label col-xs-6">Email</td>
					<td><input type="text" v-model="member.email" class="form-control"></td>
				</tr>
				<tr>
					<td class="table-label col-xs-6">Home Phone</td>
					<td><input type="text" v-model="member.home_phone" class="form-control"></td>
				</tr>
				<tr>
					<td class="table-label col-xs-6">Mobile</td>
					<td><input type="text" v-model="member.mobile_phone" class="form-control"></td>
				</tr>
				<tr>
					<td class="table-label col-xs-6">Business Phone</td>
					<td><input type="text" v-model="member.business_phone" class="form-control"></td>
				</tr>
				<tr>
					<td></td>
					<td><button class="btn btn-primary btn-sm" v-on:click="saveMember()">Save Changes</button></td>
				</tr>
			</table>

		</div>
		<div class="col-sm-6 col-xs-12">

			<table class="table table-striped">
				<tr>
					<th colspan="2">Membership</th>
				</tr>
				<tr v-if="1">
					
					<button class="ml-2 btn btn-outline-dark" v-on:click="showResignPanel=!showResignPanel">Resign Member...</button>

					<div v-if="showResignPanel" class="card mt-3">
						<div class="card-header">
							Resign Member From:
						</div>

						<div class="card-body">
							<div>
								<label for="resign_gnz">
									<input id="resign_gnz" type="checkbox" v-model="resign_gnz"> Gliding New Zealand
								</label>
							</div>
							<div v-for="affiliate in orderedAffiliates"  v-if="!affiliate.resigned" class="ml-4">
								<label :for="'affiliate_' + affiliate.id">
									<input v-model="affiliate.to_resign" :id="'affiliate_' + affiliate.id" type="checkbox"> 
									{{affiliate.org.name}} <span v-if="getMemberType(affiliate.membertype_id)">({{getMemberType(affiliate.membertype_id).name}})</span>
								</label>
							</div>

							<div class="mb-3">
								<div>Reason: </div>
								<input type="text" v-model="resign_reason" class="form-control col-8 inline">
							</div>

							<div class="mb-3">
								<div>Date: </div>
								<div class="col-4">
									<v-date-picker  v-if="member.can_edit" id="start_date" v-model="resign_date" :locale="{ id: 'start_date', firstDayOfWeek: 2, masks: { weekdays: 'WW', L: 'DD/MM/YYYY' } }" :popover="{ visibility: 'click' }"></v-date-picker>
								</div>
								
							</div>

							<button class="btn btn-outline-dark float-right" v-on:click="showResignPanel=false">Cancel</button>
							<button class="btn btn-primary" v-on:click="resignMember">Resign Member</button>
							
						</div>

					</div>

				</tr>
			</table>



			<table class="table table-striped">
				<tr>
					<th colspan="2">GNZ Details</th>
				</tr>
				<tr v-if="member.pending_approval">
					<td class="table-label col-xs-6">
						<span class="fa fa-exclamation-triangle warning"></span>
						GNZ Approval
					</td>
					<td>
						Pending Approval <br>

						<button v-show="showAdmin" class="btn btn-success" v-on:click="approveMember">Approve Member</button>
						
					</td>
				</tr>
				<tr v-if="!member.pending_approval && (member.membership_type=='Resigned' || member.membership_type==null || member.membership_type=='' || !member.nzga_number) && !showAdmin">

					<td class="table-label col-xs-6">
						<span class="fa fa-exclamation-triangle warning"></span>
						Approval Needed
					</td>
					<td>
						<button v-if="!showAdmin" class="ml-2 btn btn-success" v-on:click="requestApproval">Request GNZ Approval</button>
					</td>
				</tr>
				<tr>
					<td class="table-label col-xs-6">GNZ Number</td>
					<td>
						<input type="text" v-model="member.nzga_number" class="form-control mb-2" v-if="showAdmin">
						<span v-if="!showAdmin">{{member.nzga_number}}</span>
						
						<div v-if="!member.nzga_number && !member.pending_approval">
							<span class="fa fa-exclamation-triangle warning"></span> None
						</div>

						<button v-if="showAdmin && !member.nzga_number" class="ml-2 btn btn-outline-dark btn-sm" v-on:click="setNextGnzNumber">Set Next GNZ Number</button>

					</td>
				</tr>
				<tr>
					<td class="table-label col-xs-6">Primary Club</td>
					<td>
						<select  v-if="showAdmin" class="form-control input-sm" name="club" v-model="member.club">
							<option v-bind:value="null">None</option>
							<option v-for="org in orgs" v-bind:value="org.gnz_code">{{org.name}}</option>
						</select>

						<span v-if="!showAdmin">{{member.club}}</span>
					</td>
				</tr>
				<tr>
					<td class="table-label col-xs-6">GNZ Membership Type</td>
					<td>

						<select v-model="member.membership_type" class="form-control" v-if="showAdmin">
							<option value="Resigned">Resigned</option>
							<option value="Flying">Flying</option>
							<option value="Mag Only">Communications</option>
							<option value="VFP Bulk">Visiting Foreign Pilot Bulk</option>
							<option value="VFP 3 Mth">Visiting Foreign Pilot 3 Mth</option>
							<option value="Junior">Junior</option>
							<option value="">None</option>
						</select>

						<span v-if="!showAdmin">{{member.membership_type}}</span>

					</td>
				</tr>
				<tr v-if="showAdmin">
					<td class="table-label col-xs-6">Created</td>
					<td>{{member.created}}</td>
				</tr>
				<tr v-if="showAdmin">
					<td class="table-label col-xs-6">Modified</td>
					<td>{{member.modified}}</td>
				</tr>
				<tr v-if="showAdmin">
					<td class="table-label col-xs-6">Comments</td>
					<td><textarea class="form-control" cols="30" rows="5" v-model="member.comments"></textarea></td>
				</tr>
				<tr>
					<td></td>
					<td><button class="btn btn-primary btn-sm" v-on:click="saveMember()">Save Changes</button></td>
				</tr>
			</table>

			<table class="table table-striped table-sm">
				<tr>
					<th colspan="2">Membership Status &amp; History <a :href="'/members/' + member.id + '/edit-affiliates'" class="btn btn-outline-dark btn-sm float-right">Edit History</a></th>
				</tr>

				<template v-for="affiliate in orderedAffiliates">
					
					<tr>
						<td>{{affiliate.org.name}}</td>
						<td>
								<span v-if="getMemberType(affiliate.membertype_id)">{{getMemberType(affiliate.membertype_id).name}}</span>
								
								<span v-if="!affiliate.resigned" class="success">Active</span>
								<span v-if="affiliate.resigned" class="error">Ended/Resigned</span>

								<br>
								{{formatDate(affiliate.join_date)}} <span v-if="affiliate.end_date">to {{formatDate(affiliate.end_date)}}</span>
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
		props: ['memberId'],
		data() {
			return {
				org: null,
				memberTypes: [],
				member: [],
				orgs: [],
				showEdit: false,
				showAdmin: false,
				showResignPanel: false,
				resign_gnz: true,
				resign_reason: '',
				resign_date: null,
				waitingResign: false
			}
		},
		mounted() {
			this.org = window.Laravel.org;
			this.loadOrgs();
			this.loadMemberTypes();
			this.loadMember();
			this.resign_date = Vue.prototype.$moment().toDate(); // set to today by default
			if (window.Laravel.admin==true) this.showAdmin=true;
			if (window.Laravel.editAwards==true) this.showAdmin=true;
		},
		computed: {
			orderedAffiliates: function () {
				return _.orderBy(this.member.affiliates, ['join_date', 'resigned'], 'desc')
			}
		},
		methods: {
			getMemberType(membertypeId) {
				return this.memberTypes.find( ({ id }) => id === membertypeId);
			},
			loadMember: function() {
				var that = this;
				window.axios.get('/api/v1/members/' + this.memberId, {params: this.state}).then(function (response) {
					that.member = response.data.data;
					that.member.date_of_birth = that.$moment(that.member.date_of_birth).toDate();
					// convert dates to javascript for all affiliates
					if (that.member.affiliates) {
							that.member.affiliates.forEach(function(affiliate) {
							if (affiliate.join_date) affiliate.join_date = that.$moment(affiliate.join_date).toDate();
							if (affiliate.end_date) affiliate.end_date = that.$moment(affiliate.end_date).toDate();
							affiliate.to_resign = true;
						});
					}
				});
			},
			loadOrgs: function() {
				var that = this;
				window.axios.get('/api/v1/orgs/').then(function (response) {
					that.orgs = response.data.data;
				});
			},
			loadMemberTypes: function()
			{
				var that=this;
				window.axios.get('/api/v1/membertypes').then(function (response) {
					that.memberTypes = response.data.data;
				});
			},
			saveMember: function() {
				var that=this;

				var member = _.clone(this.member);
				member.date_of_birth = this.apiDateFormat(member.date_of_birth);

				window.axios.put('/api/v1/members/' + this.memberId, member).then(function (response) {
					messages.$emit('success', 'Member Updated');
				});
			},
			resignMember: function() {
				var that = this;
				// first resign the affiliates
				this.member.affiliates.forEach(function(affiliate) {
					if (!affiliate.resigned) {
						if (affiliate.to_resign) that.resignAffiliate(affiliate);
						//if (!affiliate.to_resign) can_resign_gnz = false; // can't resign GNZ as they are still active in this club
					}
				});

				// currently we can always resign from GNZ even if other clubs have this member as a member. Because we don't know what is an associate member vs a GNZ member yet in the database...
				// So if the box is ticked, resign them!
				if (this.resign_gnz) {
					var data = {
						resign_date: this.apiDateFormat(this.resign_date),
						resigned_comment: this.resigned_comment
					}
					window.axios.post('/api/v1/members/' + this.memberId + '/resign-gnz', data).then(function (response) {
						messages.$emit('success', 'Member Resigned from GNZ');
						that.loadMember();
					}).catch(error => {
						if (error.response) {
							that.loadMember();
							messages.$emit('error', error.response.data.error);
						}
						
					});
				}

			},
			resignAffiliate: function(affiliate) {
				affiliate.end_date = this.resign_date;
				//affiliate.end_date.set({hour:0,minute:0,second:0,millisecond:0}).toDate();
				affiliate.resigned = true;
				affiliate.resigned_comment = this.resign_reason;
				this.updateAffiliate(affiliate);
			},
			updateAffiliate: function(affiliate) {
				var that=this;
				var affiliate_clone = _.clone(affiliate);
				affiliate_clone.join_date = this.apiDateFormat(affiliate_clone.join_date);
				affiliate_clone.end_date = this.apiDateFormat(affiliate_clone.end_date);
				window.axios.put('/api/v1/affiliates/' + affiliate.id, affiliate_clone).then(function (response) {
					messages.$emit('success', 'Membership Details Updated');
					that.loadMember();
				}).catch(error => {
					if (error.response) {
						that.loadMember();
						messages.$emit('error', error.response.data.error);
					}
					
				});
			},
			setNextGnzNumber: function() {
				var that = this;
				window.axios.post('/api/v1/members/' + this.memberId + '/set-next-gnz-number').then(function (response) {
					var result = response.data.data;
					that.member.nzga_number = result.nzga_number;
					messages.$emit('success', 'GNZ Number Set to ' + that.member.nzga_number);
				});
			},
			approveMember: function() {
				// check the required items exist
				
					this.member.pending_approval=false;
					this.saveMember();
				
			},
			requestApproval: function() {
				var that=this;
				console.log(this.member);

				var errors = false;
				if (this.isEmpty(this.member.first_name)) { errors=true; messages.$emit('error', 'A first name is required'); }
				if (this.isEmpty(this.member.last_name)) { errors=true; messages.$emit('error', 'A last name is required'); }
				if (this.isEmpty(this.member.email)) { errors=true; messages.$emit('error', 'An email is required'); }
				if (this.isEmpty(this.member.gender)) { errors=true; messages.$emit('error', 'A gender is required'); }
				if (this.isEmpty(this.member.address_1)) { errors=true; messages.$emit('error', 'An address is required'); }
				if (this.isEmpty(this.member.city)) { errors=true; messages.$emit('error', 'A city is required'); }
				if (this.isEmpty(this.member.date_of_birth) || !this.isValidDate(this.member.date_of_birth)) { errors=true; messages.$emit('error', 'A date of birth is required'); }

				if (!errors) {

					window.axios.post('/api/v1/members/' + this.memberId + '/request-approval').then(function (response) {
						that.member.pending_approval=true;
						messages.$emit('success', 'Request Sent');
					});
				}
			}
		}
	}
</script>