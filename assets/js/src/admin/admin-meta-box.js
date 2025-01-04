    const adminMetaBox = () => {
        const uploadButton = document.getElementById("weebles_upload_image_button");

        if (uploadButton) {
            uploadButton.addEventListener("click", function (e) {
                e.preventDefault();

                // Open the WordPress Media Uploader.
                const mediaUploader = wp.media({
                    title: "Choose an Open Graph Image",
                    button: {
                        text: "Use this image",
                    },
                    multiple: false,
                });

                mediaUploader.on("select", function () {
                    const attachment = mediaUploader.state().get("selection").first().toJSON();

                    // Update the hidden input and preview image.
                    const hiddenInput = document.getElementById("weebles_meta_og_image");
                    const imagePreview = document.getElementById("weebles_meta_og_image_preview");

                    if (hiddenInput) {
                        hiddenInput.value = attachment.url;
                    }

                    if (imagePreview) {
                        console.log("Preview image updated");
                        imagePreview.src = attachment.url;
                        imagePreview.style.display = "block";
                        imagePreview.style.maxWidth = "100px";
                        imagePreview.style.maxHeight = "100px";
                    }
                });

                mediaUploader.open();
            });
        }
    };

    adminMetaBox();



export default adminMetaBox;