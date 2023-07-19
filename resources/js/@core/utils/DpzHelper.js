export const amountParse = amount => {
  return amount.toLocaleString('en-US',
    { style: 'currency', currency: 'INR' },
  )
}

export const resolveStatusVariant = status => {
  let txnStatus = {
    "Success": "success",
    "Failed": "danger",
    "Initialized": "primary",
    "Full Refund": "info",
    "Partial Refund": "info",
    "Processing": "warning",
    "Cancelled": "danger",
    "Not Attempted": "warning",
    "Pending": "primary",
    "Expired": "secondary",
  }
  
  return txnStatus[status]
}
