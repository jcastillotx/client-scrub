from apify_client import ApifyClient

from .config import settings


class ApifyService:
    def __init__(self):
        self.client = ApifyClient(settings.apify_api_token)

    def run_actor(self, actor_id: str, run_input: dict, webhooks: list | None = None):
        """Trigger an Apify actor run"""
        run = self.client.actor(actor_id).call(
            run_input=run_input,
            webhooks=webhooks,
        )
        return run

    def get_dataset_items(self, dataset_id: str):
        """Retrieve items from an Apify dataset"""
        dataset = self.client.dataset(dataset_id)
        items = dataset.list_items().items
        return items

    def get_run_info(self, run_id: str):
        """Get information about an actor run"""
        run = self.client.run(run_id).get()
        return run


apify_service = ApifyService()
