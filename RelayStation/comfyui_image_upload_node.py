import os
import requests
import base64
import io
from PIL import Image

class LntaImageUploader:
    """
    一个用于将本地图片上传到lnta服务器并获取URL的ComfyUI节点
    """
    def __init__(self):
        pass
    
    @classmethod
    def INPUT_TYPES(cls):
        return {
            "required": {
                "image": ("IMAGE",),
                "api_url": ("STRING", {
                      "multiline": False,
                    "default": "http://www.lnta.xyz/RelayStation/upload_api.php"
                })
            }
        }
    
    RETURN_TYPES = ("STRING",)
    RETURN_NAMES = ("image_url",)
    
    FUNCTION = "upload_image"
    
    CATEGORY = "Lnta"
    
    def upload_image(self, image, api_url):
        try:
            # 处理图像数据
            img = image[0]
            img = (img * 255).astype('uint8')
            pil_img = Image.fromarray(img)
            
            # 将图像转换为字节流
            buffer = io.BytesIO()
            pil_img.save(buffer, format="PNG")
            img_bytes = buffer.getvalue()
            
            # 准备请求数据
            files = {
                "image": ("upload.png", img_bytes, "image/png")
            }
            
            # 发送请求
            response = requests.post(api_url, files=files)
            
            # 检查响应
            if response.status_code == 200:
                data = response.json()
                if data.get("success"):
                    return (data.get("image_url"),)
                else:
                    raise Exception(f"API错误: {data.get('error')}")
            else:
                raise Exception(f"请求失败: 状态码 {response.status_code}, 响应: {response.text}")
        except Exception as e:
            print(f"上传图片时发生错误: {str(e)}")
            raise

# 注册节点
NODE_CLASS_MAPPINGS = {
    "LntaImageUploader": LntaImageUploader
}

NODE_DISPLAY_NAME_MAPPINGS = {
    "LntaImageUploader": "Lnta图片上传器"
}