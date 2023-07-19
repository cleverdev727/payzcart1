<script setup>
const PayoutDelayedTimes = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]
</script>

<template>
  <div>
    <VCard class="mb-6">
      <VCardText>Home > Setting</VCardText>
    </VCard>

    <VRow>
      <VCol
        cols="12"
        md="6"
      >
        <VCard
          title="Change Password"
          class="mb-6"
        >
          <VCardText>
            <AppTextField
              v-model="oldPassword"
              label="Old Password"
              type="password"
            />
          </VCardText>
          <VCardText>
            <AppTextField
              v-model="newPassword"
              label="New Password"
              type="password"
            />
          </VCardText>
          <VCardText>
            <AppTextField
              v-model="confirmPassword"
              label="Confirm New Password"
              type="password"
            />
          </VCardText>
          <VCardText>
            <VBtn
              block
              type="button"
              @click="changePassword"
            >
              Save
            </VBtn>
          </VCardText>
        </VCard>

        <VCard
          title="Payout Configuration"
          class="mb-6"
        >
          <VCardText>
            <AppSelect
              v-model="payoutDelayedTime"
              :items="PayoutDelayedTimes"
            />
          </VCardText>
          <VCardText>
            <VSwitch
              v-model="autoApprovedPayout"
              label="Auto Approved Payout"
            />
          </VCardText>
          <VCardText>
            <VBtn
              block
              type="button"
              @click="saveConfigration"
            >
              Save
            </VBtn>
          </VCardText>
        </VCard>
        
        <VCard
          title="Webhook"
          class="mb-6"
        >
          <VCardText>
            <AppTextField
              v-model="payinHook"
              label="Payin Webhook"
              type="text"
            />
          </VCardText>
          <VCardText>
            <AppTextField
              v-model="payoutHook"
              label="Payout Webhook"
              type="text"
            />
          </VCardText>
          <VCardText>
            <VBtn
              block
              type="button"
              @click="saveWebHook"
            >
              Save
            </VBtn>
          </VCardText>
        </VCard>
      </VCol>
      <VCol
        cols="12"
        md="6"
      >
        <VCard
          title="User's information"
          class="mb-6"
        >
          <VCardText class="d-flex justify-betwee">
            <div class="w-25">
              User Name:
            </div> <div>asdfasdfasf</div>
          </VCardText>
          <VCardText class="d-flex justify-betwee">
            <div class="w-25">
              MID:
            </div> <div>SDF_asdfasdasddsdfsdf</div>
          </VCardText>
          <VCardText class="d-flex justify-betwee">
            <div class="w-25">
              Status:
            </div> <div>Approved</div>
          </VCardText>
        </VCard>
        <VCard title="Google Authenticator :">
          <VCardText>
            <div class="text-h4">
              Secure Your Account
            </div>
            <div class="">
              Two-factor authentication adds an extra layer of security to your account. To log in, in addition you'll need to provide a 6 digit code
            </div>
            <hr class="my-3">
            <VBtn
              block
              type="button"
            >
              Enable
            </VBtn>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>

<script>
import axios from '@axios'
import { useToast } from 'vue-toast-notification'
import 'vue-toast-notification/dist/theme-sugar.css'

export default {
  data(){
    return {
      oldPassword: "",
      newPassword: "",
      confirmPassword: "",
      payoutDelayedTime: 1,
      autoApprovedPayout: true,
      payinHook: "",
      payoutHook: "",
    }
  },
  mounted(){
    axios.get('/api/setting/detail')
      .then(res=>{
        this.payinHook = res.data.data.webhook_url
        this.payoutHook = res.data.data.payout_webhook_url
        this.payoutDelayedTime = res.data.data.payout_delayed_time
        this.autoApprovedPayout = res.data.data.is_auto_approved_payout
      })
  },
  methods: {
    saveWebHook() {
      const $toast = useToast()

      axios.post('/api/setting/update/webhook', {
        payment_webhook: this.payinHook,
        payout_webhook: this.payoutHook,
      })
        .then(res=>{
          $toast.success(res.data.message)
        })
        .catch(error => {
          $toast.error(error.response.data.message)
        })
    },
    saveConfigration() {
      const $toast = useToast()

      axios.post('/api/setting/update/configuration', {
        payout_delayed_time: this.payoutDelayedTime,
        auto_approved_payout: this.autoApprovedPayout?1:0,
      })
        .then(res=>{
          $toast.success(res.data.message)
        })
        .catch(error => {
          $toast.error(error.response.data.message)
        })
    },
    changePassword() {
      const $toast = useToast()

      axios.post('/api/setting/change-password', {
        old_password: this.oldPassword,
        new_password: this.newPassword,
        confirm_password: this.confirmPassword,
      })
        .then(res=>{
          $toast.success(res.data.message)
        })
        .catch(error => {
          $toast.error(error.response.data.message)
        })
    },
  },
}
</script>
