import { INFOMATE_MIND_API_BASE_URL, INFOMATE_MIND_LLM_MODEL } from '../config.js'

export async function streamMessages(question, messageAI, onMessage, onError) {
	try {
		const apiUrl = INFOMATE_MIND_API_BASE_URL + '/stream?model=' + INFOMATE_MIND_LLM_MODEL + '&message=' + question

		const response = await fetch(apiUrl, {
			method: 'GET',
			headers: {
				'Content-Type': 'application/json',
			},
		})

		if (!response.ok) {
			console.error(response)
			throw new Error(`HTTP error! status: ${response.status}`)
		}

		const reader = response.body.getReader()
		const decoder = new TextDecoder('utf-8')
		let jsonString = ''

		while (true) {
			const { done, value } = await reader.read()
			if (done) break

			const chunk = decoder.decode(value, { stream: true })
			jsonString += chunk

			// Try to parse the accumulated JSON string
			try {
				const jsonObject = JSON.parse(jsonString)
				onMessage(jsonObject.content, messageAI)
				jsonString = '' // Reset the jsonString after successful parsing
			} catch (e) {
				// If parsing fails, continue accumulating chunks
			}
		}
	} catch (error) {
		onError(error)
	}
}
