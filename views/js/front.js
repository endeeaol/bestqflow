document.addEventListener('DOMContentLoaded', function () {
  const form = document.querySelector('#add-to-cart-or-refresh');
  const wrapper = document.querySelector('.bestqflow-product-events');

  if (!form || !wrapper) {
    return;
  }

  const hiddenInput = form.querySelector('#bestqflow_event_id') || document.getElementById('bestqflow_event_id');
  const radios = wrapper.querySelectorAll('.bestqflow-event-radio');
  const addToCartBtn = form.querySelector('button.add-to-cart[data-button-action="add-to-cart"]');

  if (!hiddenInput || !radios.length || !addToCartBtn) {
    return;
  }

  function getSelectedRadio() {
    return wrapper.querySelector('.bestqflow-event-radio:checked');
  }

  function updateAddToCartState() {
    const selected = getSelectedRadio();
    const enabled = !!selected;

    addToCartBtn.disabled = !enabled;

    if (enabled) {
      addToCartBtn.classList.remove('bestqflow-add-to-cart-disabled');
    } else {
      addToCartBtn.classList.add('bestqflow-add-to-cart-disabled');
    }
  }

  radios.forEach(function (radio) {
    radio.addEventListener('change', function () {
      hiddenInput.value = this.value;
      updateAddToCartState();
    });
  });

  form.addEventListener('submit', function (e) {
    if (!hiddenInput.value) {
      e.preventDefault();
      alert('Wybierz miasto wydarzenia przed dodaniem biletu do koszyka.');
      updateAddToCartState();
    }
  });

  updateAddToCartState();
});