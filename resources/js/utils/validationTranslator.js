export function translateValidationError(message, t) {
  if (!message) return message

  if (typeof message !== 'string') return message

  const parts = message.split(':')
  const key = parts[0]
  const param = parts[1] ? parseInt(parts[1], 10) : undefined

  if (key.startsWith('validation.')) {
    if (param !== undefined && (key.includes('Max'))) {
      return t(key, { max: param })
    }
    return t(key)
  }

  return message
}

export function translateValidationErrors(errors, t) {
  if (!errors || typeof errors !== 'object') return errors

  const translated = {}
  for (const [field, messages] of Object.entries(errors)) {
    if (Array.isArray(messages)) {
      translated[field] = messages.map(msg => translateValidationError(msg, t))
    } else {
      translated[field] = translateValidationError(messages, t)
    }
  }
  return translated
}
