<template>
	<div class="container mt--8 pb-5" id="category-card">
        <div class="row justify-content-center">
            <div class="col-lg-12">
                <div class="row justify-content-center">
                    <div class="col-lg-3" v-for="(category, index) in article_categories" v-if="index < 4">
                        <div class="card zoom-in shadow-lg rounded border-0 text-center mb-4" >
                            <img class="card-img-top" :src="'/images/knowledgebase/'+ (index + 1) +'.jpg'" alt="Card image cap">
                            <div class="card-body">
                                <h5 class="card-title">{{ category.name }}</h5>
                                <small class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</small>
                            </div>
                        </div>
                    </div>
                </div>
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