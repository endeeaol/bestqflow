{if isset($bestqflow_events) && $bestqflow_events|@count > 0}
  <div class="bestqflow-product-events" data-bestqflow-product="1">
    <div class="bestqflow-label">Wybierz miasto wydarzenia</div>

    <div class="bestqflow-options">
      {foreach from=$bestqflow_events item=event}
        <div class="bestqflow-option">
          <input
            type="radio"
            class="bestqflow-event-radio"
            name="bestqflow_event_choice"
            id="bestqflow_event_{$event.id_bestqflow_event|intval}"
            value="{$event.id_bestqflow_event|intval}"
            data-event-id="{$event.id_bestqflow_event|intval}"
            data-city="{$event.city|escape:'html':'UTF-8'}"
            data-event-date="{$event.event_date|escape:'html':'UTF-8'}"
          >

          <label
            class="bestqflow-event-label"
            for="bestqflow_event_{$event.id_bestqflow_event|intval}"
          >
            {$event.city|escape:'html':'UTF-8'}
          </label>
        </div>
      {/foreach}
    </div>

    <input
      type="hidden"
      name="bestqflow_event_id"
      id="bestqflow_event_id"
      value=""
      autocomplete="off"
    >
  </div>
{/if}