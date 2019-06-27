module.exports = {
	methods: {
		get_url_param: function(val) {
			var result = "",
			tmp = [];
			location.search.substr(1).split("&").forEach(function (item) {
				tmp = item.split("=");
				if (tmp[0] === val) result = decodeURIComponent(tmp[1]);
			});
			return result;
		},
		next: function() {
			if (this.state.page<this.last_page) this.state.page = +this.state.page + 1;
		},
		previous: function() {
			if (this.state.page>1) this.state.page = +this.state.page - 1;
		},
		createDateFromMysql: function(mysql_string)
		{
			var t, result = null;
			if( typeof mysql_string === 'string' )
			{
				t = mysql_string.split(/[- :]/);
				result = new Date(Date.UTC(t[0], t[1] - 1, t[2], t[3] || 0, t[4] || 0, t[5] || 0));
			}
			return result;
		},
		ratingExpired: function(expires) {
			if (expires==null) return false;
			return this.dateDifference(null, expires)<0;
		},
		ratingNearlyExpired: function(expires) {
			return this.dateDifference(null, expires)<50 && this.dateDifference(null, expires)>0;
		},
		dateDifference(dateString1=null, dateString2=null)
		{
			if (dateString1==null) {
				var date1 = new Date();
			} else {
				var date1 = this.createDateFromMysql(dateString1);
			}

			if (dateString2==null) {
				var date2 = new Date();
			} else {
				var date2 = this.createDateFromMysql(dateString2);
			}
			return (date2-date1)/(1000*60*60*24);
		}
	}
}