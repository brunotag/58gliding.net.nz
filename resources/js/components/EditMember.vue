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
							<option :value="null">Not Specified</option>
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
					<td><button class="btn btn-primary btn-sm" v-on:click="saveMember()" :disabled="savingMember">Save Changes</button> <span v-show="savingMember"><i  class="fas fa-cog fa-spin"></i> Please Wait</span></td>
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
					<td><button class="btn btn-primary btn-sm" v-on:click="saveMember()" :disabled="savingMember">Save Changes</button> <span v-show="savingMember"><i  class="fas fa-cog fa-spin"></i> Please Wait</span></td>
				</tr>
			</table>

		</div>
		<div class="col-sm-6 col-xs-12">


			<table class="table table-striped">
				<tr>
					<th colspan="2">GNZ Details</th>
				</tr>
				<tr v-if="member.pending_approval">
					<td class="table-label col-xs-6">
						GNZ Approval
					</td>
					<td>
						<p>
							<span class="fa fa-exclamation-triangle danger"></span> Pending Approval <br>
						</p>

						<button v-show="showAdmin" class="btn btn-success" v-on:click="approveMember">Approve Member</button>

						<div v-if="duplicates.length>0">
							<p class="mt-3">
								<span class="fa fa-exclamation-triangle warning"></span> 
								Identical Birthdays Found
							</p>
							
							<ul>
								<li v-for="duplicate in duplicates">
									<a :href="'/members/' + duplicate.id + '/edit'">
										{{duplicate.first_name}} {{duplicate.middle_name}} {{duplicate.last_name}} #{{duplicate.nzga_number}}</a>
										<br>
										{{duplicate.address_1}}, {{duplicate.city}} {{duplicate.mobile_phone}}
									
								</li>
							</ul>

						</div>

						
					</td>
				</tr>
				<tr v-if="!member.pending_approval && !member.nzga_number && !showAdmin">

					<td class="table-label col-xs-6">
						<span class="fa fa-exclamation-triangle warning"></span>
						Approval Needed
					</td>
					<td>
						<button v-if="!showAdmin" class="ml-2 btn btn-success" v-on:click="requestApproval" :disabled="loadingApproval">Request GNZ Approval</button>
						<span v-show="loadingApproval"><i  class="fas fa-cog fa-spin"></i> Please Wait</span>
					</td>
				</tr>
				<tr>
					<td class="table-label col-xs-6">GNZ Membership Type</td>
					<td>

						<select v-model="member.gnz_membertype_id" name="member_type" id="member_type" v-if="gnzMemberTypes.length>0" class="form-control">
							<option :value="null">Not a GNZ Member</option>
							<option v-for="memberType in gnzMemberTypes" :value="memberType.id">{{memberType.name}}</option>
						</select>
						<span v-if="gnzMemberTypes.length==0">Loading...</span>

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
						<select  class="form-control input-sm" name="club" v-model="member.club">
							<option v-bind:value="null">None</option>
							<option v-for="org in orgs" v-bind:value="org.gnz_code">{{org.name}}</option>
						</select>
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
					<td><button class="btn btn-primary btn-sm" v-on:click="saveMember()" :disabled="savingMember">Save Changes</button> <span v-show="savingMember"><i  class="fas fa-cog fa-spin"></i> Please Wait</span></td>
				</tr>
			</table>

			<table class="table table-striped table-sm">
				<tr>
					<th colspan="2">
						Membership Status &amp; History 
						<a :href="'/members/' + member.id + '/edit-affiliates'" class="btn btn-link btn-sm float-right">Edit History</a> 
						<button class="ml-2 btn btn-outline-dark btn-sm float-right mr-2" v-on:click="showResignPanel=!showResignPanel">Resign Member...</button>
					</th>
				</tr>

				<tr v-if="showResignPanel"><td colspan="2">
					<div class="card mt-3">
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
								<div>
									<v-date-picker  v-if="member.can_edit" id="start_date" v-model="resign_date" :locale="{ id: 'start_date', firstDayOfWeek: 2, masks: { weekdays: 'WW', L: 'DD/MM/YYYY' } }" :popover="{ visibility: 'click' }"></v-date-picker>
								</div>
								
							</div>

							<button class="btn btn-outline-dark float-right" v-on:click="showResignPanel=false">Cancel</button>
							<button class="btn btn-primary" v-on:click="resignMember" :disabled="loadingReSign">Resign Member</button>

							<span v-show="loadingResign"><i  class="fas fa-cog fa-spin"></i> Please Wait</span>

							
						</div>

					</div>
				</td></tr>

				<template v-for="affiliate in orderedAffiliates">
					
					<tr>
						<td>{{affiliate.org.name}}</td>
						<td>

							<div v-if="!affiliate.showChange">
								<button v-if="!affiliate.resigned" class="btn btn-outline-dark btn-xs float-right" v-on:click="affiliate.showChange=!affiliate.showChange">Change Membership Type</button>

								<span v-if="getMemberType(affiliate.membertype_id)">{{getMemberType(affiliate.membertype_id).name}}</span>
								
								<span v-if="!affiliate.resigned" class="success">Active</span>
								<span v-if="affiliate.resigned" class="error">Ended/Resigned</span>

								<br>
								From {{formatDate(affiliate.join_date)}} <span v-if="affiliate.end_date">to {{formatDate(affiliate.end_date)}}</span>
							</div>

							<div v-if="affiliate.showChange">
								<div v-if="filteredMembershipTypes(affiliate.org.id).length==0">
									<span class="fa fa-exclamation-triangle danger"></span> Club Member Types haven't been set up for {{affiliate.org.name}}. A club administrator can add them at <a href="/admin/member-types" class="alert-link">/admin/member-types</a>
									<button class="btn btn-outline-dark btn-sm" v-on:click="affiliate.showChange=false">Cancel</button>

								</div>
								<div v-if="filteredMembershipTypes(affiliate.org.id).length>0">
									Change from {{getMemberType(affiliate.membertype_id).name}} to:
									<select v-model="affiliate.cloneMemberType" name="member_type" id="member_type" v-if="memberTypes.length>0" class="form-control mb-2">
										<option v-for="memberType in filteredMembershipTypes(affiliate.org.id)" :value="memberType.id">{{memberType.name}}</option>
									</select>

									<label for="member_type">Date of change</label> 
									<v-date-picker id="change_date" v-model="affiliate.changeDate" :locale="{ id: 'change_date', firstDayOfWeek: 2, masks: { weekdays: 'WW', L: 'DD/MM/YYYY' } }" :popover="{ visibility: 'click' }"></v-date-picker>
									{{affiliate.change_date}}
									
									<div>
										Does the GNZ membership type need to change too?
										<select v-model="member.gnz_membertype_id" name="member_type" id="member_type" v-if="gnzMemberTypes.length>0" class="form-control">
											<option :value="null">Not a GNZ Member</option>
											<option v-for="memberType in gnzMemberTypes" :value="memberType.id">{{memberType.name}}</option>
										</select>
									</div>

									<div class="mt-2">
										<button class="btn btn-primary btn-sm" v-on:click="changeType(affiliate)">Change Type</button>
										<button class="btn btn-outline-dark btn-sm" v-on:click="affiliate.showChange=false">Cancel</button>
									</div>
								</div>


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
		props: ['memberId'],
		data() {
			return {
				org: null,
				memberTypes: [],
				gnzMemberTypes: [],
				member: [],
				orgs: [],
				showEdit: false,
				showAdmin: false,
				showResignPanel: false,
				resign_gnz: true,
				resign_reason: '',
				resign_date: null,
				waitingResign: false,
				duplicates: [], // any member duplicates, used when approving a member,
				loadingApproval: false,
				loadingResign: false,
				savingMember: false
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
				if (membertypeId==null) return {'id': null, 'name': 'None'};
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
							Vue.set(affiliate, 'showChange', false);
							Vue.set(affiliate, 'changeDate', that.$moment().toDate());

							if (affiliate.join_date) affiliate.join_date = that.$moment(affiliate.join_date).toDate();
							if (affiliate.end_date) affiliate.end_date = that.$moment(affiliate.end_date).toDate();
							affiliate.to_resign = true;
						});
					}

					if (that.member.pending_approval) {
						that.loadDuplicates();
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
				window.axios.get('/api/v1/membertypes?org_id=' + 30).then(function (response) {
					that.gnzMemberTypes = response.data.data;
				});
			},
			saveMember: function() {
				var that=this;
				this.savingMember = true;

				var member = _.clone(this.member);
				member.date_of_birth = this.apiDateFormat(member.date_of_birth);

				window.axios.put('/api/v1/members/' + this.memberId, member).then(function (response) {
					that.savingMember = false;
					messages.$emit('success', 'Member Updated');
				});
			},
			loadDuplicates: function() {
				if (!window.Laravel.admin) return;
				var that=this;
				window.axios.get('/api/v1/members/' + this.memberId + '/find-duplicates').then(function (response) {
					that.duplicates = response.data.data;
				});
			},
			resignMember: function() {
				var that = this;
				this.loadingResign = true;
				// first resign the affiliates
				this.member.affiliates.forEach(function(affiliate) {
					if (!affiliate.resigned) {
						if (affiliate.to_resign) that.resignAffiliate(affiliate);
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
						that.showResignPanel = false;
						that.loadingResign = false;
						that.loadMember();
					}).catch(error => {
						if (error.response) {
							that.loadMember();
							that.showResignPanel = false;
							that.loadingResign = false;
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
					messages.$emit('success', 'Club Membership Details Updated');
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
				this.member.needs_gnz_approval = false;
				this.saveMember();
			},
			requestApproval: function() {
				var that=this;
				this.loadingApproval = true;

				var errors = false;
				if (this.isEmpty(this.member.first_name)) { errors=true; messages.$emit('error', 'A first name is required'); }
				if (this.isEmpty(this.member.last_name)) { errors=true; messages.$emit('error', 'A last name is required'); }
				if (this.isEmpty(this.member.email)) { errors=true; messages.$emit('error', 'An email is required'); }
				if (this.isEmpty(this.member.address_1)) { errors=true; messages.$emit('error', 'An address is required'); }
				if (this.isEmpty(this.member.city)) { errors=true; messages.$emit('error', 'A city is required'); }
				if (this.isEmpty(this.member.date_of_birth) || !this.isValidDate(this.member.date_of_birth)) { errors=true; messages.$emit('error', 'A date of birth is required'); }

				if (errors) {
					this.loadingApproval = false;
				}

				if (!errors) {
					this.saveMember();

					window.axios.post('/api/v1/members/' + this.memberId + '/request-approval').then(function (response) {
						that.member.pending_approval=true;
						that.loadingApproval = false;
						messages.$emit('success', 'Request Sent');
					});
				}
			},
			// filter membership types by the given org ID
			filteredMembershipTypes: function(org_id) {
				if (this.memberTypes.length==0) return [];
				return this.memberTypes.filter( function (m) {
					if (m.org_id == org_id) return true;
				});
			},
			changeType: function(affiliate)
			{
				var that = this;
				// create a new affilliate with the new details

				that.saveMember();

				window.axios.post('/api/v1/affiliates', {
					org_id: affiliate.org.id, 
					member_id: this.member.id, 
					membertype_id: affiliate.cloneMemberType,
					join_date: this.apiDateFormat(affiliate.changeDate)
				}).then(function (response) {

					// set the current affiliate to ended
					affiliate.end_date = affiliate.changeDate;
					affiliate.resigned = true;
					affiliate.resigned_comment = 'Changed Membership Type';
					that.updateAffiliate(affiliate, true);

				}).catch(
					function (error) {
						messages.$emit('error', error.response.data.error);
					}
				);
			}
		}
	}
</script>