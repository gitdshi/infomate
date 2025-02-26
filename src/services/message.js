import { INFOMATE_MIND_API_BASE_URL } from '../config.js'

export async function streamMessages(question, messageAI, onMessage, onError) {
	try {
		const apiUrl = INFOMATE_MIND_API_BASE_URL + '/anything/streamchat'

		const controller = new AbortController()
		const timeoutId = setTimeout(() => controller.abort(), 300000) // 5 mins timeout

		const response = await fetch(apiUrl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
			},
			body: JSON.stringify({
				workspace: 'admin',
				thread: String(messageAI.chatId),
				message: question,
			}),
			signal: controller.signal,
		})

		clearTimeout(timeoutId)

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

export async function loadMessages(workspace, onError) {
	const apiUrl = `${INFOMATE_MIND_API_BASE_URL}/anything/messages`

	try {
		const response = await fetch(`${apiUrl}?workspace=${workspace}`, {
			method: 'GET',
			headers: {
				'Content-Type': 'application/json',
			},
		})

		if (!response.ok) {
			throw new Error(`HTTP error! status: ${response.status}`)
		}

		return await response.json()
	} catch (error) {
		onError(error)
		return null
	}
}
