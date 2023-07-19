<script setup>
const props = defineProps({
  summary: {
    type: Object,
    required: true,
  },
})
</script>

<template>
  <VRow class="mb-6">
    <VCol
      cols="12"
      sm="6"
      md="3"
    >
      <VCard>
        <VCardText>
          <div class="pb-2">
            Total Payout
          </div>
          <div class="pb-2">
            {{ props.summary?.total_payout }}
          </div>
        </VCardText>
      </VCard>        
    </VCol>
    <VCol
      cols="12"
      sm="6"
      md="3"
    >
      <VCard>
        <VCardText>
          <div class="pb-2">
            PAYOUT AMOUNT
          </div>
          <div class="pb-2">
            ₹ {{ props.summary?.payout_amount }}
          </div>
        </VCardText>
      </VCard>        
    </VCol>
    <VCol
      cols="12"
      sm="6"
      md="3"
    >
      <VCard>
        <VCardText>
          <div class="pb-2">
            Total Payout Amount
          </div>
          <div class="pb-2">
            ₹ {{ props.summary?.total_payout_amount }}
          </div>
        </VCardText>
      </VCard>        
    </VCol>
    <VCol
      cols="12"
      sm="6"
      md="3"
    >
      <VCard>
        <VCardText>
          <div class="pb-2">
            Total Payout Fees
          </div>
          <div class="pb-2">
            ₹ {{ props.summary?.total_payout_fees }}
          </div>
        </VCardText>
      </VCard>        
    </VCol>
  </VRow>
</template>
