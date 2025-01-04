import { createRoot } from "react-dom/client";
import { useState, useCallback } from "react";
import {
	PanelRow,
	TabPanel,
	TextControl,
	SnackbarList,
	BaseControl,
	SelectControl,
	FormToggle,
} from "@wordpress/components";
import { updateSettings } from "./api.js";
import formFieldsData from "./settingsFields.json";

// Debounce function to limit the frequency of calling a function
const debounce = (func, wait) => {
	let timeout;
	return (...args) => {
		clearTimeout(timeout);
		timeout = setTimeout(() => func(...args), wait);
	};
};

const textControlTypes = [
	"text",
	"email",
	"url",
	"password",
	"number",
	"search",
	"tel",
	"date",
	"time",
	"datetime-local",
];

import useOgImageUploader from "./og-image-uploader";
import adminMetaBox from './admin-meta-box';

// adminMetaBox();

const SettingsPage = () => {
	const [settings, setSettings] = useState(
		window["wpRigThemeSettings"].settings,
	);
	const [snackbarNotices, setSnackbarNotices] = useState([]);
	const { imagePreview, handleUploadClick } = useOgImageUploader(
        settings["_weebles_meta_og_image"] || "",
        (url) => {
            handleChange("_weebles_meta_og_image", url);
        }
    );

	const debouncedUpdateSettings = useCallback(
		debounce((newSettings) => {
			updateSettings(newSettings).then((response) => {
				if (response.success) {
					const newSnackbarNotices = [
						...snackbarNotices,
						{
							id: Date.now(),
							content: "Settings saved!",
							spokenMessage: "Settings saved!",
						},
					];
					setSnackbarNotices(newSnackbarNotices);
					setTimeout(() => {
						setSnackbarNotices((prevNotices) =>
							prevNotices.filter(
								(notice) =>
									notice.id !== newSnackbarNotices[0].id,
							),
						);
					}, 2000);
				} else {
					console.error("Failed to save settings:", response);
				}
			});
		}, 1500),
		[snackbarNotices],
	);

	const handleChange = (settingKey, value) => {
		const newSettings = { ...settings, [settingKey]: value };
		setSettings(newSettings);
		debouncedUpdateSettings(newSettings);
	};

	return (
		<div className="settings-page">
	<TabPanel
		tabs={formFieldsData.tabs.map((tab) => ({
			name: tab.id,
			title: tab["tabControl"].label,
		}))}
	>
		{(tab) => (
			<div>
				{formFieldsData.tabs
					.find((t) => t.id === tab.name)["tabContent"].fields.map((field) => (
						<PanelRow key={field.name}>
							{field.type === "toggle" && (
								<BaseControl label={field.label}>
									<FormToggle
										checked={!!settings[field.name]}
										onChange={(event) =>
											handleChange(
												field.name,
												event.target.checked,
											)
										}
									/>
								</BaseControl>
							)}
							{field.type === "select" && (
								<SelectControl
									__nextHasNoMarginBottom
									label={field.label}
									value={settings[field.name] || ""}
									onChange={(value) =>
										handleChange(field.name, value)
									}
									options={field.options}
								/>
							)}
							{textControlTypes.includes(field.type) && (
								<TextControl
									label={field.label}
									type={field.type}
									value={settings[field.name] || ""}
									onChange={(value) =>
										handleChange(field.name, value)
									}
								/>
							)}
							{/* Add OG Image Uploader */}
							{field.type === "og-image-uploader" && (
								<div className="og-image-uploader">
									<BaseControl label={field.label}>
										<button
											type="button"
											className="button"
											onClick={handleUploadClick}
										>
											Upload Image
										</button>
										{imagePreview && (
											<img
												src={imagePreview}
												alt="OG Image Preview"
												style={{
													maxWidth: "100%",
													marginTop: "1em",
												}}
											/>
										)}
									</BaseControl>
								</div>
							)}
						</PanelRow>
					))}
			</div>
		)}
	</TabPanel>
	<div id="settings-saved">
		<SnackbarList notices={snackbarNotices} />
	</div>
</div>

	);
};

export default SettingsPage;

const renderSettingsPage = () => {
	const container = document.getElementById("wp-rig-settings-page");
	if (container) {
		const root = createRoot(container);
		root.render(<SettingsPage />);
	}
};

document.addEventListener("DOMContentLoaded", renderSettingsPage);