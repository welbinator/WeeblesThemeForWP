import { useState } from "react";

const useOgImageUploader = (currentImage, onImageSelect) => {
    const [imagePreview, setImagePreview] = useState(currentImage);

    const handleUploadClick = () => {
        console.log("Upload button clicked!"); // Add this for debugging
        const mediaUploader = wp.media({
            title: "Choose OG Image",
            button: {
                text: "Use this image",
            },
            multiple: false,
        });
    
        mediaUploader.on("select", () => {
            const attachment = mediaUploader.state().get("selection").first().toJSON();
            console.log("Image selected:", attachment); // Debugging
            setImagePreview(attachment.url);
            onImageSelect(attachment.url); // Call the provided callback with the selected image URL
        });
    
        mediaUploader.open();
    };

    return {
        imagePreview,
        handleUploadClick,
    };
};

export default useOgImageUploader;
