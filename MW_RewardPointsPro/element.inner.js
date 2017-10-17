if (typeof element.textContent !== "undefined") {
	element.textContent = element.textContent.replace(/([0-9]+)/gi, totalPoints);
} else {
	element.innerText = element.innerText.replace(/([0-9]+)/gi, totalPoints);
}

