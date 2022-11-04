<template>
	<div>
        <template v-if="Object.values(logistics).find((logistic) => logistic.selected)">
            <div id="index-table" class="table-responsive overflow-hidden">
                <table class="table align-items-center table-flush">
                    <thead class="thead-light">
                    <tr>
                        <th>Logistic</th>
                        <th class="text-center">Estimated Shipping Fee</th>
                    </tr>
                    </thead>
                    <tbody class="list" v-for="logistic in logistics" v-if="logistic.selected">
                        <tr>
                            <td>{{ logistic.logistic_name }}</td>
                            <td class="text-center">
                                <span>{{ (logistic.shipping_fee) ? '$' + logistic.shipping_fee : '-' }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!--<ul class="list-group list-group-flush list my&#45;&#45;3">
                <li class="list-group-item d-flex justify-content-between align-items-center px-0" v-for="logistic in logistics" v-if="logistic.selected">
                    {{ logistic.logistic_name }}
                    <span>(${{ logistic.shipping_fee }})</span>
                </li>
            </ul>-->
        </template>
        <template v-else>
            <h5 class="text-warning text-uppercase">No Selected Logistic</h5>
        </template>

	</div>
</template>

<script>
	export default {
		name: 'ShowShopeeLogisticComponent',
		props: ['logistics'],

		updated () {}
	}
</script>
