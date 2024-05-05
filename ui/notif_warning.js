// @author      Renzi, Angelica

    function showWarningNotificationForFiveSeconds(warningMessage) {
        // Call createSection function to create the section
        createSection(warningMessage);
    
        // After 5 seconds, hide the section
        setTimeout(function() {
            const section = document.getElementById('warningNotif');
            if (section) {
                section.remove();
            }
        }, 5000); // 5000 milliseconds = 5 seconds
    }

    function createSection(notificationText) {
        console.log("Creating section...");

        // Create section element
        const section = document.createElement('section');
        section.id = 'warningNotif';

        // Create comment node
        const comment = document.createComment(' We have detected that you Tab switched ');

        // Create div element
        const div = document.createElement('div');
        div.className = 'flex items-center p-2 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 border border-red-500';
        div.setAttribute('role', 'alert');
        div.setAttribute('style', 'position: fixed; top: 60px; right: 10px; border: red');

        // Create SVG element
        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.className = 'flex-shrink-0 inline w-2 h-2 me-2'; // Adjusted size
        svg.setAttribute('aria-hidden', 'true');
        svg.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
        svg.setAttribute('fill', 'currentColor');
        svg.setAttribute('viewBox', '0 0 20 20');
        svg.setAttribute('width', '15'); // Set width to 20
        svg.setAttribute('height', '15'); // Set height to 20

        // Create path inside SVG
        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        path.setAttribute('d', 'M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z');

        // Append path to SVG
        svg.appendChild(path);

        // Create span element
        const span = document.createElement('span');
        span.className = 'sr-only';
        span.textContent = 'Info';

        // Create inner div
        const innerDiv = document.createElement('div');

        // Create text content for inner div
        const warningText = document.createElement('strong');
        warningText.textContent = 'Warning! ';
        innerDiv.appendChild(warningText);

        // Create text content for inner div
        const textNode = document.createTextNode(notificationText);


        // Append text node to inner div
        innerDiv.appendChild(textNode);
        innerDiv.setAttribute('style', 'padding-left: 10px;'); // Set left padding to 10px

        // Append SVG, span, and inner div to div
        div.appendChild(svg);
        div.appendChild(span);
        div.appendChild(innerDiv);

        // Append comment and div to section
        section.appendChild(comment);
        section.appendChild(div);

        // Append section to document body
        document.body.appendChild(section);

        // Get the bounding rectangle of the section
        const sectionDimensions = section.getBoundingClientRect();

        // Set the width and height of the section dynamically
        section.style.width = sectionDimensions.width + 'px';
        section.style.height = sectionDimensions.height + 'px';
    }
