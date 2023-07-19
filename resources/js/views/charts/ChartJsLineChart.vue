<script setup>
import { getLineChartConfig } from '@core/libs/chartjs/chartjsConfig'
import LineChart from '@core/libs/chartjs/components/LineChart'
import { useTheme } from 'vuetify'

const vuetifyTheme = useTheme()

const chartConfig = computed(() => getLineChartConfig(vuetifyTheme.current.value))
</script>

<template>
  <LineChart
    :chart-options="chartConfig"
    :chart-data="chartData"
  />
</template>

<script>
export default {
  props: {
    cdata: Object,
  },
  data() {
    return {
      test: "asdfasdf",
      chartData: {
        labels: [],
        datasets: [
          {
            fill: false,
            tension: 0,
            pointRadius: 1,
            label: 'Payin',
            pointHoverRadius: 5,
            pointStyle: 'circle',
            borderColor: '#836af9',
            backgroundColor: '#836af9',
            pointHoverBorderWidth: 5,
            pointHoverBorderColor: '#fff',
            pointBorderColor: 'transparent',
            pointHoverBackgroundColor: '#836af9',
            data: [],
          },
          {
            fill: true,
            tension: 0,
            label: 'Payout',
            pointRadius: 1,
            pointHoverRadius: 5,
            pointStyle: 'circle',
            borderColor: '#ffbd1f',
            backgroundColor: '#ffbd1f',
            pointHoverBorderWidth: 5,
            pointHoverBorderColor: '#fff',
            pointBorderColor: 'transparent',
            pointHoverBackgroundColor: '#ffbd1f',
            data: [],
          },
        ],
      },
    }
  },
  watch: {
    cdata(value){
      let data = {
        ...this.chartData,
        labels: value?.chartCategories,
        datasets: [
          {
            ...this.chartData.datasets[0],
            data: value?.chartInSeriesAmount,
          },
          {
            ...this.chartData.datasets[1],
            data: value?.chartOutSeriesAmount,
          },
        ],
      }
      this.chartData = data
      console.log(this.chartData)
    },
  },
}
</script>
