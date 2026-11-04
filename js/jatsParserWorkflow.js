/**
 * @file plugins/generic/jatsParser/js/jatsParserWorkflow.js
 *
 * Copyright (c) 2017-2026 SEDICI / Vitalii Bezsheiko
 * Distributed under the GNU GPL v3.
 *
 * @brief Vue 3 component and workflow integration for the "Texto completo JATS" publication tab.
 */

(function () {
	function registerJatsParserWorkflow() {
		if (
			typeof pkp === 'undefined' ||
			!pkp.registry ||
			!pkp.registry._piniaInstance ||
			typeof pkp.registry.registerComponent !== 'function' ||
			typeof pkp.registry.storeExtendFn !== 'function'
		) {
			setTimeout(registerJatsParserWorkflow, 25);
			return;
		}

		pkp.registry.registerComponent('JatsParserPublicationSection', {
	name: 'JatsParserPublicationSection',

	props: {
		submission: {
			type: Object,
			required: true,
		},
		publication: {
			type: Object,
			required: true,
		},
		canEdit: {
			type: Boolean,
			default: false,
		},
	},

	data() {
		return {
			isLoading: true,
			formConfig: null,
			errorMessage: '',
			targetDeleteLocale: null,
			targetDeleteLocaleName: '',
			showDeleteModal: false,
			isDeleting: false,
		};
	},

	template: `
		<div class="jats-parser-workflow-container" style="padding: 1rem 0;">
			<div v-if="isLoading" style="text-align: center; padding: 2rem;">
				<pkp-spinner />
			</div>

			<div v-else-if="errorMessage" class="pkp_notification" style="margin-bottom: 1rem;">
				<div class="notifyError">
					<span class="title">{{ t('error') }}</span>
					<span class="description">{{ errorMessage }}</span>
				</div>
			</div>

			<div v-else-if="formConfig">
				<pkp-form v-bind="formConfig" @set="setForm" />
			</div>

			<!-- Modal nativo de confirmación para eliminar HTML -->
			<div v-if="showDeleteModal" class="citation-modal" style="display: block;">
				<div class="citation-modal-content" style="max-width: 500px; margin: 10% auto; background: #fff; border-radius: 4px; padding: 24px; box-shadow: 0 5px 20px rgba(0,0,0,0.3); border: 1px solid rgba(0,0,0,0.15); position: relative;">
					<span class="citation-modal-close" @click="closeDeleteModal" style="position: absolute; right: 18px; top: 14px; font-size: 24px; color: #888; cursor: pointer; line-height: 1;">&times;</span>
					<h2 style="margin-top: 0; margin-bottom: 14px; font-size: 1.15rem; font-weight: 700; color: #222;">
						{{ t('deleteConfirmTitle') }}
					</h2>
					<p style="margin-bottom: 12px; font-size: 0.9rem; line-height: 1.45; color: #444;">
						{{ t('deleteConfirmText') }} <span v-if="targetDeleteLocaleName">({{ targetDeleteLocaleName }})</span>
					</p>
					<div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; padding-top: 15px; border-top: 1px solid #e5e5e5;">
						<button type="button" class="pkpButton" @click="closeDeleteModal">
							{{ t('cancel') }}
						</button>
						<button type="button" class="pkpButton pkpButton--isWarnable" :disabled="isDeleting" @click="confirmDeleteHtml">
							{{ t('deleteBtn') }}
						</button>
					</div>
				</div>
			</div>
		</div>
	`,

	watch: {
		'publication.id': function (newVal, oldVal) {
			if (newVal !== oldVal) {
				this.loadFormConfig();
			}
		},
	},

	mounted() {
		this.loadFormConfig();
		this.bindDomListeners();
	},

	methods: {
		t(key) {
			const cfg = (window.pkp && window.pkp.const && window.pkp.const.jatsParserConfig) || window.jatsParserConfig || {};
			return cfg[key] || key;
		},

		async loadFormConfig() {
			this.isLoading = true;
			this.errorMessage = '';

			const cfg = (window.pkp && window.pkp.const && window.pkp.const.jatsParserConfig) || window.jatsParserConfig || {};
			const formUrl = cfg.formConfigUrl;

			if (!formUrl) {
				this.errorMessage = 'jatsParserConfig.formConfigUrl is not defined';
				this.isLoading = false;
				return;
			}

			const url = new URL(formUrl, window.location.origin);
			url.searchParams.set('submissionId', this.submission.id);
			url.searchParams.set('publicationId', this.publication.id);

			try {
				const response = await fetch(url.toString(), {
					headers: {
						'X-Csrf-Token': pkp.currentUser ? pkp.currentUser.csrfToken : '',
					},
				});
				const data = await response.json();
				if (data && data.status === true && data.content) {
					this.formConfig = data.content;
					window.jatsPublicationApiUrl = this.formConfig.action;

					// Wait for Vue DOM to update, then initialize citation table handlers
					this.$nextTick(() => {
						if (window.jatsInitCitationTable) {
							window.jatsInitCitationTable();
						}
					});
				} else {
					this.errorMessage = data.content || 'Failed to load form config';
				}
			} catch (e) {
				this.errorMessage = e.message || 'Error fetching form configuration';
			} finally {
				this.isLoading = false;
			}
		},

		setForm(formId, data) {
			if (this.formConfig && this.formConfig.id === formId) {
				Object.assign(this.formConfig, data);
			}
		},

		openDeleteModal(locale, localeName) {
			this.targetDeleteLocale = locale;
			this.targetDeleteLocaleName = localeName;
			this.showDeleteModal = true;
		},

		closeDeleteModal() {
			this.showDeleteModal = false;
			this.targetDeleteLocale = null;
			this.targetDeleteLocaleName = '';
			this.isDeleting = false;
		},

		async confirmDeleteHtml() {
			if (this.isDeleting) return;
			this.isDeleting = true;

			const locale = this.targetDeleteLocale;
			const apiUrl = window.jatsPublicationApiUrl || (this.formConfig && this.formConfig.action);

			if (!apiUrl || !locale) {
				alert(this.t('error') + ': Target locale or API URL is missing.');
				this.closeDeleteModal();
				return;
			}

			const payload = {
				'jatsParser::deleteHtml': {
					[locale]: true,
				},
			};

			try {
				const response = await fetch(apiUrl, {
					method: 'PUT',
					headers: {
						'Content-Type': 'application/json',
						'X-Csrf-Token': pkp.currentUser ? pkp.currentUser.csrfToken : '',
					},
					body: JSON.stringify(payload),
				});

				if (!response.ok) throw new Error(response.statusText || 'Request failed');

				if (window.pkp && window.pkp.eventBus) {
					window.pkp.eventBus.$emit('notify', this.t('deletedSuccess'), 'success');
				}
				this.closeDeleteModal();
				// Reload form configuration
				await this.loadFormConfig();
			} catch (err) {
				alert(this.t('error') + (err.message ? ': ' + err.message : ''));
				this.isDeleting = false;
			}
		},

		bindDomListeners() {
			const self = this;

			// Handle clicks on .jatsDeleteHtmlBtn generated inside FieldOptions / FieldHTML
			document.addEventListener('click', function (e) {
				const btn = e.target ? e.target.closest('.jatsDeleteHtmlBtn, #jatsDeleteHtmlBtn') : null;
				if (btn) {
					e.preventDefault();
					e.stopPropagation();
					const locale = btn.dataset.locale || '';
					const localeName = btn.dataset.localename || locale;
					self.openDeleteModal(locale, localeName);
				}
			});

			// Escuchar evento form-success nativo de OJS al presionar "Guardar"
			if (window.pkp && window.pkp.eventBus) {
				window.pkp.eventBus.$on('form-success', function (formId, response) {
					if (formId === 'jatsUpload') {
						self.loadFormConfig();
					}
				});
			}

			// Advertir si el usuario selecciona otro archivo XML en el selector de arriba sin guardar
			document.addEventListener('change', function (e) {
				if (e.target && e.target.type === 'radio') {
					const nameAttr = e.target.getAttribute('name') || '';
					if (nameAttr.indexOf('fullTextFileId') !== -1 || (e.target.closest && e.target.closest('[id*="fullTextFileId"]'))) {
						const labelElem = e.target.closest('label');
						const textElem = labelElem ? (labelElem.querySelector('.pkpFormFieldLabel_text') || labelElem) : null;
						const newlySelectedXml = textElem ? textElem.textContent.trim() : '';

						const badgeElem = document.querySelector('.citation-selected-xml-badge');
						const currentTableXml = badgeElem ? (badgeElem.getAttribute('data-current-xml') || '').trim() : '';

						const alertBox = document.getElementById('citationUnsavedXmlAlert');
						const alertText = document.getElementById('citationUnsavedXmlAlertText');

						if (alertBox && alertText && newlySelectedXml && currentTableXml) {
							if (newlySelectedXml !== currentTableXml) {
								alertText.innerHTML = '<strong>' + self.t('notice') + ':</strong> ' +
									'Ha seleccionado "<strong>' + newlySelectedXml + '</strong>" en el selector de arriba. Para que la tabla de citas se genere con este nuevo archivo, presione "Guardar" en la esquina inferior derecha.';
								alertBox.style.display = 'flex';
							} else {
								alertBox.style.display = 'none';
							}
						}
					}
				}
			});
		},
	},
});

		/**
		 * Add a custom "JATSParser" secondary tab under Publication.
		 */
		pkp.registry.storeExtendFn('workflow', 'getMenuItems', function (originalItems, args) {
			const cfg = window.jatsParserConfig || (window.pkp && window.pkp.const && window.pkp.const.jatsParserConfig) || {};
			const tabLabel = (cfg && cfg.tabLabel) ? cfg.tabLabel : 'JATSParser';

			return originalItems.map(function (item) {
				if (item.key === 'publication' && Array.isArray(item.items)) {
					// Avoid duplicate tab registration
					if (item.items.some(function (i) { return i.key === 'publication_jatsUpload' || i.key === 'publication_jatsparser'; })) {
						return item;
					}
					return Object.assign({}, item, {
						items: item.items.concat([
							{
								key: 'publication_jatsUpload',
								label: tabLabel,
								state: {
									primaryMenuItem: 'publication',
									secondaryMenuItem: 'jatsUpload',
									title: tabLabel,
								},
							},
						]),
					});
				}
				return item;
			});
		});

		/**
		 * Inject JatsParserPublicationSection into the "jatsUpload" publication tab.
		 */
		pkp.registry.storeExtendFn(
			'workflow',
			'getPrimaryItems',
			function (originalItems, args) {
				if (
					args &&
					args.selectedMenuState &&
					args.selectedMenuState.primaryMenuItem === 'publication' &&
					(args.selectedMenuState.secondaryMenuItem === 'jatsUpload' || args.selectedMenuState.secondaryMenuItem === 'jatsparser')
				) {
					const publication =
						args.selectedPublication ||
						(args.submission && args.submission.publications && args.selectedMenuState.publicationId
							? args.submission.publications.find(function (p) { return p.id === args.selectedMenuState.publicationId; })
							: null) ||
						(args.submission && args.submission.publications
							? (args.submission.publications.find(function (p) { return p.status === 1; }) ||
							   args.submission.publications[args.submission.publications.length - 1])
							: null);

					return [
						{
							component: 'JatsParserPublicationSection',
							props: {
								submission: args.submission,
								publication: publication,
								canEdit: args.permissions
									? args.permissions.canEditPublication
									: false,
							},
						},
					];
				}

				return originalItems;
			}
		);
	}

	registerJatsParserWorkflow();
})();
