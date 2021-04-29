<template>
<div>
	<h1 v-if="member" class="results-title"><a href="/members">Members</a> &raquo; <a :href="'/members/' + member.id">{{member.first_name}} {{member.last_name}}</a> &raquo; <a :href="'/members/' + member.id + '/edit'">Edit</a> &raquo; Membership History</h1>


	<div class="alert alert-danger" role="alert">
		<strong><span class="fa fa-exclamation-triangle danger"></span> Warning</strong> Only edit the membership history if a mistake was made or correction needed. If a member is changing type of membership, e.g. Associate to Flying member, change it on the <a :href="'/members/' + member.id + '/edit'" class="alert-link">Edit Member page.</a> Otherwise features like billing and statistics won't be correct.
	</div>


	<table class="table table-striped table-sm collapsable" v-if="member && memberTypes">
		<tr>
			<th>Club</th>
			<th>Membership Type</th>
			<th>Status</th>
			<th>Start</th>
			<th colspan="2">End</th>
			<th>Comment</th>
			<th></th>
		</tr>

		<tr v-for="(affiliate, index) in orderedAffiliates" v-bind:key="affiliate.id">
			<td>{{affiliate.org.name}}</td>
			<td>
				<select v-model="affiliate.membertype_id" name="member_type" id="member_type" v-if="memberTypes.length>0" class="form-control">
					<option v-for="memberType in filteredMembershipTypes(affiliate.org.id)" :value="memberType.id">{{memberType.name}}</option>
				</select>
			</td>
			<td>
					<span v-if="!affiliate.resigned" class="success">Active</span>
					<span v-if="affiliate.resigned" class="error">Ended</span>
			</td>
			<td>
				<v-date-picker id="join_date" v-model="affiliate.join_date" :locale="{ id: 'join_date', firstDayOfWeek: 2, masks: { weekdays: 'WW', L: 'DD/MM/YYYY' } }" :popover="{ visibility: 'click' }"></v-date-picker>
			</td>
			<td style="white-space: nowrap;">
				
				<label v-show="affiliate.resigned" :for="'resigned'+affiliate.id"><input :id="'resigned'+affiliate.id" type="checkbox" :value="true" v-model="affiliate.resigned"> Ended</label>
			</td>
			<td>
				<button v-if="!affiliate.resigned" class="btn btn-danger btn-sm " v-on:click="resignAffiliate(affiliate)">End Now</button>

				<span  v-show="affiliate.resigned">
					<!-- <input type="text" v-model="affiliate.end_date" class="form-control"> -->
					<v-date-picker id="end_date" v-model="affiliate.end_date" :locale="{ id: 'end_date', firstDayOfWeek: 2, masks: { weekdays: 'WW', L: 'DD/MM/YYYY' } }" :popover="{ visibility: 'click' }"></v-date-picker>

				</span>

			</td>
			<td>
				<input type="text" v-model="affiliate.resigned_comment" class="form-control">
			</td>
			<td class="text-nowrap">
				<button class="btn btn-primary btn-sm mb-1 mr-2" v-on:click="updateAffiliate(affiliate, false)">Save</button>
				<button class="btn btn-primary btn-sm" v-on:click="affiliate.showDelete=!affiliate.showDelete">Delete</button>
				<div v-if="affiliate.showDelete">
					Are you sure?<br>
					<button class="btn btn-primary btn-xs" v-on:click="affiliate.showDelete=false">Cancel</button>
					<button class="btn btn-danger btn-xs" v-on:click="deleteAffiliate(affiliate)">Yes, Delete</button>
				</div>
				
			</td>
			
		</tr>

		
	</table>
</div>
</template>
<script>
	import common from '../../mixins.js';

	export default {
		mixins: [common],
		props: ['memberId'],
		data() {
			return {
				org: null,
				memberTypes: [],
				member: [],
				showEdit: false,
				showAdmin: false,
				showChangeType: [],
			}
		},
		mounted() {
			this.org = window.Laravel.org;
			this.loadMemberTypes();
			this.loadMember();
			if (window.Laravel.admin==true) this.showAdmin=true;
			if (window.Laravel.editAwards==true) this.showAdmin=true;
		},
		computed: {
			orderedAffiliates: function () {
				return _.orderBy(this.member.affiliates, ['join_date', 'resigned'], 'desc')
			}
		},
		methods: {
			// filter membership types by the given org ID
			filteredMembershipTypes: function(org_id) {
				console.log(org_id);
				return this.memberTypes.filter( function (m) {
					if (m.org_id == org_id) return true;
				});
			},
			getMemberType(membertypeId) {
				var result=  this.memberTypes.find( ({ id }) => id === membertypeId);
				if (result) return result;
				return 'UNKOWN';
			},
			loadMember: function() {
				var that = this;
				window.axios.get('/api/v1/members/' + this.memberId, {params: this.state}).then(function (response) {
					that.member = response.data.data;
					that.member.date_of_birth = that.$moment(that.member.date_of_birth).toDate();
					// convert dates to javascript for all affiliates
					if (that.member.affiliates) {
							that.member.affiliates.forEach(function(affiliate) {

								Vue.set(affiliate, 'showEdit', false); // add flag and make it reactive
								Vue.set(affiliate, 'showChange', false); // add flag and make it reactive
								Vue.set(affiliate, 'showDelete', false); // add flag and make it reactive
								Vue.set(affiliate, 'cloneMemberType', null); // add flag and make it reactive

								if (affiliate.join_date) affiliate.join_date = that.$moment(affiliate.join_date).toDate();
								if (affiliate.end_date) affiliate.end_date = that.$moment(affiliate.end_date).toDate();
						});
					}
				});
			},
			loadMemberTypes: function()
			{
				var that=this;
				window.axios.get('/api/v1/membertypes').then(function (response) {
					that.memberTypes = response.data.data;
				});
			},
			updateAffiliate: function(affiliate, reload) {
				var that=this;
				var affiliate_clone = _.clone(affiliate);
				affiliate_clone.join_date = this.apiDateFormat(affiliate_clone.join_date);
				affiliate_clone.end_date = this.apiDateFormat(affiliate_clone.end_date);

				window.axios.put('/api/v1/affiliates/' + affiliate.id, affiliate_clone).then(function (response) {
					messages.$emit('success', 'Membership Details Updated');
					if (reload) that.loadMember();


				}).catch(error => {
					if (error.response) {
						messages.$emit('error', error.response.data.error);
					}
					
				});
			},
			resignAffiliate: function(affiliate) {
				affiliate.end_date = Vue.prototype.$moment().toDate();
				//affiliate.end_date.set({hour:0,minute:0,second:0,millisecond:0}).toDate();
				affiliate.resigned = true;
				this.updateAffiliate(affiliate, false);
			},
			deleteAffiliate: function(affiliate) {
				var that=this;
				window.axios.delete('/api/v1/affiliates/' + affiliate.id).then(function (response) {
					messages.$emit('success', 'Membership History Deleted');
					that.loadMember();

				}).catch(error => {
					if (error.response) {
						messages.$emit('error', error.response.data.error);
					}
					
				});
			}
		}
	}
</script>