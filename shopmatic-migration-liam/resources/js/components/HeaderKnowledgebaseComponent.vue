<template>
	<div class="container pb-5">
	    <div class="row justify-content-center">
	        <div class="col-lg-10">
	            <form class="navbar-search navbar-search-dark ml-lg-auto">
	                <div class="form-group mb-0">
	                    <div class="input-group input-group-alternative">
	                        <div class="input-group-prepend">
	                            <button class="btn bg-gradient-blue text-white dropdown-toggle rounded-pill border-0" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Category</button>
	                            <div class="dropdown-menu">
	                                <a class="dropdown-item" :href="'/knowledgebase/'+category.id" v-for="category in article_categories">{{category.name}}</a>
	                            </div>
	                        </div>
	                        <input type="text" class="form-control"  v-model="keyword" placeholder="Find answers (pricing, account, product, order ...)?">
	                    </div>
	                </div>
	                <div class="row justify-content-center mt-4 ">
	                    <span class="text-white mr-2"><small>Suggestions : </small></span>
	                    <template v-if="article_categories">
	                        <a href="#" class="badge bg-gradient-lighter badge-pill text-default mr-2" v-for="(category, index) in article_categories" v-if="index < 5" @click="selectArticle(category)">#{{ category.name}}</a>
	                    </template>
	                </div>
	            </form>
	        </div>
	    </div>
	</div>
</template>

<script>
	export default {
		name: "IndexKnowledgebaseComponent",
		data() {
			return {
				article_categories: {},
				keyword: '',
				articles: {}
			}
		},
		watch: {
			keyword: function(newKeyword, oldKeyword) {
                console.log(this.keyword)
                this.debouncedSearchArticle()
            }
		},
		methods: {
			getCategories: function()
            {
                axios({method:'GET', url: '/web/article/categories' }).then((response) => {
                    let data = response.data;

                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        this.article_categories = data.response.items;
                    }
                }).catch(function (error) {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            selectArticle: function(category)
            {
            	axios({method:'GET', url: '/web/articles', params: {category: category.name} }).then((response) => {
                    let data = response.data;

                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                     console.log(data)   
                    }
                }).catch(function (error) {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            searchArticle: function()
            {
            	axios({method:'GET', url: '/web/articles', params: {filter: this.keyword} }).then((response) => {
                    let data = response.data;

                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                  		this.articles = data.response.items;
                  		console.log(this.articles);
                    }
                }).catch(function (error) {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            }
		},
		mounted() {

			this.getCategories();

			this.debouncedSearchArticle = _.debounce(this.searchArticle, 2000)
		}
	}
</script>