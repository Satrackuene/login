(function () {
	const form = document.querySelector("#segp-form");
	if (!form) return;
	const email = form.querySelector("#segp-email");
	const msg = form.querySelector("#segp-msg");
	const btn = form.querySelector("#segp-btn");
	const endpoint = form.dataset.endpoint;
	const nonce = form.dataset.nonce;
	form.addEventListener("submit", async function (e) {
		e.preventDefault();
		msg.textContent = "";
		btn.disabled = true;
		try {
			const res = await fetch(endpoint, {
				method: "POST",
				headers: { "Content-Type": "application/json", "X-WP-Nonce": nonce },
				body: JSON.stringify({ email: (email.value || "").trim() }),
			});
			const data = await res.json().catch(() => ({ message: "Error" }));
			if (res.ok && data.ok) {
				msg.textContent = form.dataset.success || "Acceso concedido. Recargando…";
				window.location.reload();
			} else {
				msg.textContent = data.message || "No autorizado";
			}
		} catch (err) {
			msg.textContent = "Error de red, intenta de nuevo.";
		} finally {
			btn.disabled = false;
		}
	});
})();
