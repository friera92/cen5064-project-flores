<script setup lang="ts">
const name = ref('')
const email = ref('')
const phone = ref('')
const address = ref('')
const password = ref('')
const passwordConfirmation = ref('')

const loading = ref(false)
const errorMessage = ref('')

const { register } = useAuth()

const showPassword = ref(false)
const showPasswordConfirmation = ref(false)

const passwordRequirements = computed(() => ({
		length: password.value.length >= 8,
		uppercase: /[A-Z]/.test(password.value),
		lowercase: /[a-z]/.test(password.value),
		number: /[0-9]/.test(password.value),
		symbol: /[^A-Za-z0-9]/.test(password.value)
}))

const isPasswordValid = computed(() =>
	Object.values(passwordRequirements.value).every(Boolean)
)

async function handleRegister() {
		errorMessage.value = ''

		if (!isPasswordValid.value) {
		errorMessage.value =
				'Password does not meet all requirements.'
		return
		}

		if (password.value !== passwordConfirmation.value) {
				errorMessage.value = 'Passwords do not match.'
				return
		}

		loading.value = true

		try {
				await register({
				name: name.value,
				email: email.value,
				phone: phone.value || null,
				address: address.value || null,
				password: password.value,
				password_confirmation: passwordConfirmation.value
				})

				await navigateTo('/')
		} catch (error: any) {
				console.error(error)

				if (error?.status === 422) {
				const errors = error?.data?.errors

				if (errors) {
						const firstError = Object.values(errors).flat()[0]

						errorMessage.value =
						typeof firstError === 'string'
								? firstError
								: 'Please check the information provided.'
				} else {
						errorMessage.value =
						'Please check the information provided.'
				}
				} else {
				errorMessage.value =
						'Unable to create your account. Please try again.'
				}
		} finally {
				loading.value = false
		}
}
</script>

<template>
	<div class="min-h-screen bg-[#FAF9F6]">
		<AppHeader />

		<main
			class="mx-auto flex max-w-7xl justify-center px-6 py-16"
		>
			<div
				class="w-full max-w-md rounded-2xl border
							 border-[#E5E9E4] bg-white p-8 shadow-sm"
			>
				<div class="mb-8 text-center">
					<h1
						class="text-3xl font-bold text-[#172B25]"
					>
						Create your account
					</h1>

					<p class="mt-2 text-sm text-[#66736D]">
						Join NeighbourLend and start sharing with your community
					</p>
				</div>

				<form
					class="space-y-5"
					@submit.prevent="handleRegister"
				>
					<label class="block">
						<span
							class="mb-2 block text-sm font-medium
										 text-[#172B25]"
						>
							Name
						</span>

						<input
							v-model="name"
							type="text"
							required
							autocomplete="name"
							class="w-full rounded-lg border
										 border-[#E5E9E4] px-4 py-3
										 outline-none focus:border-[#176B52]"
							placeholder="Your name"
						>
					</label>

					<label class="block">
						<span
							class="mb-2 block text-sm font-medium
										 text-[#172B25]"
						>
							Email
						</span>

						<input
							v-model="email"
							type="email"
							required
							autocomplete="email"
							class="w-full rounded-lg border
										 border-[#E5E9E4] px-4 py-3
										 outline-none focus:border-[#176B52]"
							placeholder="you@example.com"
						>
					</label>

					<label class="block">
						<span
							class="mb-2 block text-sm font-medium
										 text-[#172B25]"
						>
							Phone
							<span class="font-normal text-[#66736D]">
								(optional)
							</span>
						</span>

						<input
							v-model="phone"
							type="tel"
							autocomplete="tel"
							class="w-full rounded-lg border
										 border-[#E5E9E4] px-4 py-3
										 outline-none focus:border-[#176B52]"
							placeholder="(305) 555-0123"
						>
					</label>

					<label class="block">
						<span
							class="mb-2 block text-sm font-medium
										 text-[#172B25]"
						>
							Address
							<span class="font-normal text-[#66736D]">
								(optional)
							</span>
						</span>

						<input
							v-model="address"
							type="text"
							autocomplete="street-address"
							class="w-full rounded-lg border
										 border-[#E5E9E4] px-4 py-3
										 outline-none focus:border-[#176B52]"
							placeholder="Your address"
						>
					</label>

					 <label class="block">
								<span
										class="mb-2 block text-sm font-medium
												text-[#172B25]">
										Password
								</span>

								<div class="relative">
										<input
										v-model="password"
										:type="showPassword ? 'text' : 'password'"
										required
										autocomplete="new-password"
										class="w-full rounded-lg border
														border-[#E5E9E4] px-4 py-3 pr-20
														outline-none focus:border-[#176B52]"
										placeholder="Create a password"
										>

										<button
										type="button"
										class="absolute right-3 top-1/2
														-translate-y-1/2 text-sm font-medium
														text-[#176B52] hover:text-[#124D3D]"
										@click="showPassword = !showPassword"
										>
										{{ showPassword ? 'Hide' : 'Show' }}
										</button>
								</div>
						</label>

						<div class="space-y-1 text-xs">
								<p
										:class="
										passwordRequirements.length
												? 'text-[#176B52] font-bold'
												: 'text-[#66736D]'
										"
								>
										✓ At least 8 characters
								</p>

								<p
										:class="
										passwordRequirements.uppercase
												? 'text-[#176B52] font-bold'
												: 'text-[#66736D]'
										"
								>
										✓ One uppercase letter
								</p>

								<p
										:class="
										passwordRequirements.lowercase
												? 'text-[#176B52] font-bold'
												: 'text-[#66736D]'
										"
								>
										✓ One lowercase letter
								</p>

								<p
										:class="
										passwordRequirements.number
												? 'text-[#176B52] font-bold'
												: 'text-[#66736D]'
										"
								>
										✓ One number
								</p>

								<p
										:class="
										passwordRequirements.symbol
												? 'text-[#176B52] font-bold'
												: 'text-[#66736D]'
										"
								>
										✓ One special character
								</p>
						</div>

						<label class="block">
								<span
										class="mb-2 block text-sm font-medium
												text-[#172B25]"
								>
										Confirm password
								</span>

								<div class="relative">
										<input
										v-model="passwordConfirmation"
										:type="showPasswordConfirmation ? 'text' : 'password'"
										required
										autocomplete="new-password"
										class="w-full rounded-lg border
														border-[#E5E9E4] px-4 py-3 pr-20
														outline-none focus:border-[#176B52]"
										placeholder="Confirm your password"
										>

										<button
										type="button"
										class="absolute right-3 top-1/2
														-translate-y-1/2 text-sm font-medium
														text-[#176B52] hover:text-[#124D3D]"
										@click="
												showPasswordConfirmation =
												!showPasswordConfirmation
										"
										>
										{{ showPasswordConfirmation ? 'Hide' : 'Show' }}
										</button>
								</div>
						</label>

					<p
						v-if="errorMessage"
						class="text-sm text-red-600"
					>
						{{ errorMessage }}
					</p>

					<button
						type="submit"
						:disabled="loading"
						class="w-full rounded-lg bg-[#176B52]
									 px-4 py-3 font-semibold text-white
									 transition hover:bg-[#124D3D]
									 disabled:cursor-not-allowed
									 disabled:opacity-60"
					>
						{{ loading ? 'Creating account...' : 'Create account' }}
					</button>
				</form>

				<p class="mt-6 text-center text-sm text-[#66736D]">
					Already have an account?

					<NuxtLink
						to="/login"
						class="font-semibold text-[#176B52]
									 hover:text-[#124D3D]"
					>
						Log in
					</NuxtLink>
				</p>
			</div>
		</main>
	</div>
</template>