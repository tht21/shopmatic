<template>
    <form id="create-form" role="form" @submit.prevent="create">
        <div class="row">
            <div class="card mx-md-auto col-md-8">
                <div class="card-body">
                    <h1 class="font-weight-light mb-3 text-center">Create Article</h1>
                    <div class="form-group mb-3">
                        <div class="input-group input-group-merge input-group-alternative">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="ni ni-caps-small"></i></span>
                            </div>
                            <input name="title" class="form-control" placeholder="Title" type="text" />
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <textarea name="outline" class="form-control" placeholder="Outline" v-model="outline"></textarea>
                    </div>
                    <div class="form-group mb-3">
                        <tinymce-vue :model.sync="description" type="article"/>
                    </div>
                </div>
            </div>
            <div class="card mx-md-auto col-md-3">
                <div class="card-body">
                    <h1 class="font-weight-light mb-3 text-center">Article Details</h1>
                    <div class="form-group mb-3">
                        <div class="input-group input-group-merge input-group-alternative">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="ni ni-books"></i></span>
                            </div>
                            <select class="form-control" v-model="selected_category" name="article_categories_id">
                                <option value="0">Select Category</option>
                                <option v-for="category in article_categories" :value="category.id">{{ category.name }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <div>
                            <label class="typo__label">Tagging</label>
                            <multiselect v-model="selected_tags" tag-placeholder="Add this as new tag" placeholder="Search or add a tag" label="name" track-by="id" :options="article_tags" :multiple="true" :taggable="true" @tag="addTag"></multiselect>
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <div class="input-group input-group-merge input-group-alternative">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="ni ni-align-left-2"></i></span>
                            </div>
                            <select class="form-control" placeholder="Select Categories" v-model="selected_article" name="parent_id">
                                <option value="0">Select Parent Article</option>
                                <option v-for="article in articles" :value="article.id">{{ article.title }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-12 text-center pb-5">
                        <button type="submit" class="btn btn-info mb-2 mt-4 px-5">Create Article</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</template>

<script>
    import TinymceVue from "../../utility/TinymceVue";

    export default {
        name: "CreateArticleComponent",
        components: {
            TinymceVue,
        },
        props: ['index_url', 'request_url'],
        data() {
            return {
                articles: {},
                article_categories: {},
                selected_category: 0,
                selected_article: 0,
                outline: '',
                description: '',
                article_tags: [],
                selected_tags: null,
            }
        },
        methods: {
            retrieve: function()
            {

            },
            getCategories: function()
            {
                axios({method:'GET', url: '/web/articles/category' }).then((response) => {
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
            getArticles: function()
            {
                axios({method:'GET', url: '/web/articles' }).then((response) => {
                    let data = response.data;

                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        this.articles = data.response.items;
                    }
                }).catch(function (error) {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            getArticleTags: function()
            {
                axios({method:'GET', url: '/web/articles/tag' }).then((response) => {
                    let data = response.data;

                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        this.article_tags = data.response.items;
                    }
                }).catch(function (error) {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            addTag (newTag) {
                const tag = {
                    name: newTag
                };
                this.article_tags.push(tag);
                this.selected_tags.push(tag);
            },
            create: function()
            {
                let formData = new FormData($('#create-form')[0]);
                let tags = [];
                let obj = this.selected_tags;

                Object.keys(obj).forEach(function(key){
                   tags.push(obj[key].name);
                });

                formData.append('description', this.description);
                formData.append('article_tags', tags);

                axios({method:'POST', url: this.index_url, data: formData }).then((response) => {
                    let data = response.data;

                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        swal({
                            title: 'Success',
                            text: 'You have successfully updated the article category.',
                            type: 'success',
                            buttonsStyling: false,
                            confirmButtonClass: 'btn btn-success'
                        }).then(() => {
                            window.location.href = '/admin/articles';
                        });
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
        created() {
            this.getCategories();
            this.getArticles();
            this.getArticleTags();
        },
        mounted() {
            $("#input-tags").selectize({
                delimiter: ',',
                persist: false,
                create: function(input) {
                    return {
                        value: input,
                        text: input
                    }
                }
            });
        }
    }
</script>
